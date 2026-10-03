<?php
// Dump every registered route to ROUTES.md grouped by URI prefix.
// Tenant routes live inside `if ($domain)` in routes/web.php — that block
// reads `request()->getHost()` at file-include time. We bind a fake Request
// with a known tenant host BEFORE kernel bootstrap so those routes register.

require __DIR__ . '/../vendor/autoload.php';

// --- 1. Discover a real tenant domain (quick boot, no route registration yet).
$discover = require __DIR__ . '/../bootstrap/app.php';
$discover->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tenantDomain = optional(\Illuminate\Support\Facades\DB::table('domains')->first())->domain ?? 'localhost';
unset($discover);

// --- 2. Fresh container with the spoofed request bound before bootstrap.
$app = require __DIR__ . '/../bootstrap/app.php';
$req = Illuminate\Http\Request::create('http://' . $tenantDomain . '/', 'GET');
$app->instance('request', $req);
Illuminate\Http\Request::setTrustedProxies([], 0);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Re-bind in case bootstrap replaced it (then re-include routes if registrar ran early).
$app->instance('request', $req);

$router = $app->make('router');
$routes = $router->getRoutes()->getRoutes();

$groups = [];
foreach ($routes as $r) {
    $uri = $r->uri();
    $name = $r->getName() ?? '';
    $methods = array_diff($r->methods(), ['HEAD']);
    $action = ltrim($r->getActionName(), '\\');
    $middleware = implode('|', array_map(
        fn ($m) => is_string($m) ? $m : (is_object($m) ? get_class($m) : 'closure'),
        $r->gatherMiddleware(),
    ));
    $parts = explode('/', $uri);
    $group = $parts[0] ?: '/';
    if (in_array($group, ['admin','merchant','super-admin','api'], true) && isset($parts[1])) {
        $group .= '/' . preg_replace('/\{.*$/', '*', $parts[1]);
    }
    $groups[$group][] = [
        'methods'    => implode(',', $methods),
        'uri'        => '/' . $uri,
        'name'       => $name,
        'action'     => $action,
        'middleware' => $middleware,
    ];
}

ksort($groups);

$total = array_sum(array_map('count', $groups));
$out  = "# Application Routes\n\n";
$out .= "_Generated " . date('Y-m-d H:i:s') . " UTC. Tenant-domain-gated routes were exposed by spoofing host `{$tenantDomain}` before kernel bootstrap._\n\n";
$out .= "Total routes: **{$total}** across **" . count($groups) . "** groups.\n\n";
$out .= "## Table of contents\n\n";
foreach (array_keys($groups) as $g) {
    $anchor = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $g));
    $out .= "- [`{$g}`](#" . trim($anchor, '-') . ") — " . count($groups[$g]) . " routes\n";
}
$out .= "\n";

foreach ($groups as $g => $list) {
    usort($list, fn($a,$b) => strcmp($a['uri'].$a['methods'], $b['uri'].$b['methods']));
    $out .= "## `{$g}`\n\n";
    $out .= "| Method | URI | Name | Action | Middleware |\n";
    $out .= "|---|---|---|---|---|\n";
    foreach ($list as $row) {
        $action = $row['action'];
        if (str_contains($action, '@')) {
            [$cls, $m] = explode('@', $action, 2);
            $action = class_basename($cls) . '@' . $m;
        } elseif (str_contains($action, '\\Closure')) {
            $action = 'Closure';
        }
        $mw = preg_replace('/[A-Za-z0-9_\\\\]+\\\\/', '', $row['middleware']);
        $name = $row['name'] !== '' ? "`{$row['name']}`" : '';
        $out .= "| {$row['methods']} | `{$row['uri']}` | {$name} | `{$action}` | <sub>{$mw}</sub> |\n";
    }
    $out .= "\n";
}

file_put_contents(__DIR__ . '/../ROUTES.md', $out);
echo "Wrote ROUTES.md — {$total} routes across " . count($groups) . " groups (spoofed host: {$tenantDomain})\n";
