<?php
/**
 * Audit every translation key referenced in source against lang/en (the
 * fallback locale). A key that does not resolve renders as the literal key
 * string on the page — and when it is wrapped in the `__('x.y') ?: 'Default'`
 * idiom the default is dead code, because the returned key is truthy.
 */

$root = $argv[1] ?? getcwd();

// Some lang files reference app enums (e.g. lang/en/AccountHeads.php), so the
// composer autoloader has to be in place before including them.
if (is_file("$root/vendor/autoload.php")) {
    require "$root/vendor/autoload.php";
}

// ---- 1. Load the English catalog ------------------------------------------
$catalog = [];
foreach (glob("$root/lang/en/*.php") as $file) {
    $group = basename($file, '.php');
    try {
        $data = include $file;
    } catch (\Throwable $e) {
        fwrite(STDERR, "  (skipped lang/en/$group.php: {$e->getMessage()})\n");
        continue;
    }
    if (is_array($data)) {
        $catalog[$group] = $data;
    }
}
$json = [];
if (is_file("$root/lang/en.json")) {
    $json = json_decode(file_get_contents("$root/lang/en.json"), true) ?: [];
}

function resolves(array $catalog, array $json, string $key): bool
{
    if (!str_contains($key, '.')) {
        return array_key_exists($key, $json);
    }
    [$group, $rest] = explode('.', $key, 2);
    if (!isset($catalog[$group])) {
        return false;
    }
    // Mirror Illuminate\Support\Arr::get: an EXACT key wins before the dotted
    // path is walked, so a lang file entry literally named 'P.Charge' resolves.
    $node = $catalog[$group];
    if (array_key_exists($rest, $node)) {
        return true;
    }
    foreach (explode('.', $rest) as $seg) {
        if (!is_array($node) || !array_key_exists($seg, $node)) {
            return false;
        }
        $node = $node[$seg];
    }
    return true;
}

// ---- 2. Walk the source ----------------------------------------------------
$dirs = ['app', 'resources/views', 'routes', 'database'];
$files = [];
foreach ($dirs as $d) {
    $path = "$root/$d";
    if (!is_dir($path)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && preg_match('/\.(php|blade\.php)$/', $f->getFilename())) {
            $files[] = $f->getPathname();
        }
    }
}

// __('key'), trans('key'), @lang('key') — single or double quoted, no interpolation.
$pattern = '/(?:__|trans|@lang)\(\s*([\'"])([a-zA-Z0-9_\-]+(?:\.[a-zA-Z0-9_\-]+)*)\1\s*\)/';

$missing = [];   // key => [ ['file'=>, 'line'=>, 'masked'=>bool], ... ]
$total = 0;

foreach ($files as $file) {
    $src   = file_get_contents($file);
    $lines = explode("\n", $src);
    foreach ($lines as $i => $line) {
        if (!preg_match_all($pattern, $line, $m, PREG_SET_ORDER)) continue;
        foreach ($m as $hit) {
            $key = $hit[2];
            $total++;
            if (resolves($catalog, $json, $key)) continue;
            // Is this call wrapped in the `?: 'default'` idiom on the same line?
            $masked = (bool) preg_match(
                '/' . preg_quote($hit[0], '/') . '\s*\?:/',
                $line
            );
            $missing[$key][] = [
                'file'   => ltrim(str_replace($root, '', $file), '/'),
                'line'   => $i + 1,
                'masked' => $masked,
            ];
        }
    }
}

// ---- 3. Classify and report ----------------------------------------------
// A key only misbehaves when the author MEANT a translation. Three cases:
//   A) group file exists but the key is absent -> renders "group.key". A bug.
//   B) dotted, but the group is not a lang file -> almost always a literal
//      ("0.00", "1.5") or a whole group that was never created.
//   C) no dot -> the __('Some English text') JSON pattern; returning the key
//      IS the intended English, so this is correct behaviour, not a bug.
$A = $B = $C = [];
foreach ($missing as $key => $hits) {
    if (!str_contains($key, '.')) { $C[$key] = $hits; continue; }
    $group = explode('.', $key, 2)[0];
    if (isset($catalog[$group])) { $A[$key] = $hits; } else { $B[$key] = $hits; }
}
$sites = fn(array $set) => array_sum(array_map('count', $set));
$idiom = function (array $set) {
    $n = 0;
    foreach ($set as $hits) foreach ($hits as $h) if ($h['masked']) $n++;
    return $n;
};

echo "Scanned {$total} translation calls across " . count($files) . " files.\n\n";
printf("A) REAL BUGS   group exists, key missing : %3d keys / %4d sites  (%d via the ?: idiom)\n", count($A), $sites($A), $idiom($A));
printf("B) suspicious  dotted, no such group     : %3d keys / %4d sites  (%d via the ?: idiom)\n", count($B), $sites($B), $idiom($B));
printf("C) fine        no dot, JSON literal      : %3d keys / %4d sites\n\n", count($C), $sites($C));

echo str_repeat('=', 78) . "\nA) REAL BUGS — these render the literal key on the page\n" . str_repeat('=', 78) . "\n";
ksort($A);
foreach ($A as $key => $hits) {
    $m = count(array_filter($hits, fn($h) => $h['masked']));
    echo str_pad($key, 46) . str_pad($m ? "[?: idiom x$m]" : "[bare]", 16) . count($hits) . " site(s)\n";
    foreach (array_slice($hits, 0, 2) as $h) {
        echo "      {$h['file']}:{$h['line']}\n";
    }
    if (count($hits) > 2) echo "      ... +" . (count($hits) - 2) . " more\n";
}

echo "\n" . str_repeat('=', 78) . "\nB) group not found (whole missing groups, or numeric literals)\n" . str_repeat('=', 78) . "\n";
$byGroup = [];
foreach ($B as $key => $hits) { $byGroup[explode('.', $key, 2)[0]][$key] = count($hits); }
ksort($byGroup);
foreach ($byGroup as $g => $keys) {
    printf("  %-24s %2d key(s), %3d site(s)\n", $g, count($keys), array_sum($keys));
}
