<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jetstream-style "your active browser sessions" screen. Works for both
 * `database` and `file` session drivers — for file, we walk the sessions
 * dir and pick out rows carrying the current user's auth guard key.
 *
 * "Log out other browser sessions" re-authenticates via password and
 * calls Auth::logoutOtherDevices(), which rotates the remember_token so
 * any other session for this user becomes invalid on its next request.
 */
class BrowserSessionsController extends Controller
{
    /**
     * Shared entry — returns the active-sessions list for the current user
     * plus a translation bundle, callable from any controller (Profile page,
     * account settings, etc.). Kept static so callers don't have to resolve
     * an instance of this controller from the container.
     */
    public static function sessionsPayload(Request $request): array
    {
        $c = app(self::class);
        return [
            'sessions' => $c->sessions($request),
            't'        => $c->translations(),
        ];
    }

    /**
     * Log out the current user's other browser sessions. Password confirmed
     * so a stolen active session can't wipe the user's other sessions on its
     * own — matches Jetstream's guard.
     */
    public function destroy(Request $request, Hasher $hasher)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->input('password'));

        $this->deleteOtherSessionRecords($request);

        return back()->with('status', __('browser_sessions.logged_out'));
    }

    /**
     * Collect the active sessions belonging to the current user. Returns
     * an empty list when we can't enumerate (e.g. the driver isn't one we
     * know how to walk) — the current session card still renders.
     */
    private function sessions(Request $request): array
    {
        $driver = config('session.driver');

        if ($driver === 'database') {
            return $this->fromDatabase($request);
        }
        if ($driver === 'file') {
            return $this->fromFiles($request);
        }
        return [];
    }

    private function fromDatabase(Request $request): array
    {
        $userId = Auth::id();
        if (! $userId) return [];

        try {
            return DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $userId)
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn ($s) => $this->shape([
                    'id'            => (string) $s->id,
                    'ip'            => (string) ($s->ip_address ?? ''),
                    'user_agent'    => (string) ($s->user_agent ?? ''),
                    'last_activity' => (int) ($s->last_activity ?? 0),
                ], $request))
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function fromFiles(Request $request): array
    {
        // Bypass Stancl's per-tenant storage remap — session files always live at
// the framework's central path regardless of the tenant currently bound.
$dir = base_path('storage/framework/sessions');
        if (! is_dir($dir)) return [];

        $userId       = (int) Auth::id();
        $guardKey     = 'login_web_'.sha1(\Illuminate\Auth\SessionGuard::class);
        $currentId    = $request->session()->getId();

        $rows = [];
        foreach (@scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            if (! is_file($path)) continue;

            $raw = @file_get_contents($path);
            if ($raw === false || $raw === '') continue;

            // File sessions store the payload serialize()d after Laravel
            // decrypts the file handler value. Try to unserialize the raw
            // contents; skip anything we can't read.
            try {
                $decoded = @unserialize($raw);
                if (! is_array($decoded)) continue;
            } catch (\Throwable $e) { continue; }

            // Bind session → user via the auth guard key.
            if (! isset($decoded[$guardKey]) || (int) $decoded[$guardKey] !== $userId) {
                continue;
            }

            $rows[] = $this->shape([
                'id'            => (string) $entry,
                'ip'            => (string) ($decoded['_ip'] ?? $decoded['ip_address'] ?? ''),
                'user_agent'    => (string) ($decoded['_ua'] ?? $decoded['user_agent'] ?? ''),
                'last_activity' => (int) @filemtime($path) ?: 0,
            ], $request, $currentId);
        }

        // Sort newest first.
        usort($rows, fn ($a, $b) => ($b['last_activity_ts'] ?? 0) <=> ($a['last_activity_ts'] ?? 0));
        return $rows;
    }

    private function shape(array $row, Request $request, ?string $currentId = null): array
    {
        $isCurrent = $currentId
            ? ($row['id'] === $currentId)
            : ($row['ip'] === $request->ip() && ($row['user_agent'] ?? '') === (string) $request->userAgent());

        // If the session file hadn't been stamped yet (older sessions from
        // before the RecordSessionMetadata middleware), the current session
        // still gets a legible label because we know the browser's UA from
        // the request in hand.
        $ua = (string) ($row['user_agent'] ?? '');
        if ($ua === '' && $isCurrent) {
            $ua = (string) $request->userAgent();
        }
        $ip = (string) ($row['ip'] ?? '');
        if ($ip === '' && $isCurrent) {
            $ip = (string) $request->ip();
        }

        return [
            'id'                => $row['id'],
            'is_current'        => $isCurrent,
            'ip'                => $ip,
            'ua'                => $ua,
            'browser'           => $this->parseBrowser($ua),
            'platform'          => $this->parsePlatform($ua),
            'last_activity_ts'  => (int) $row['last_activity'],
            'last_activity_iso' => $row['last_activity'] ? date('c', $row['last_activity']) : null,
        ];
    }

    private function parseBrowser(string $ua): string
    {
        $ua = trim($ua);
        if ($ua === '') return 'Unknown browser';
        foreach ([
            'Edg/'      => 'Edge',
            'OPR/'      => 'Opera',
            'Firefox/'  => 'Firefox',
            'Chrome/'   => 'Chrome',
            'Safari/'   => 'Safari',
            'MSIE'      => 'Internet Explorer',
            'Trident/'  => 'Internet Explorer',
            'curl/'     => 'curl',
        ] as $needle => $label) {
            if (str_contains($ua, $needle)) return $label;
        }
        return 'Unknown browser';
    }

    private function parsePlatform(string $ua): string
    {
        $ua = trim($ua);
        if ($ua === '') return 'Unknown platform';
        if (preg_match('/iPhone|iPad|iPod/i', $ua))                return 'iOS';
        if (str_contains($ua, 'Android'))                          return 'Android';
        if (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) return 'macOS';
        if (str_contains($ua, 'Windows'))                          return 'Windows';
        if (str_contains($ua, 'Linux'))                            return 'Linux';
        return 'Unknown platform';
    }

    /**
     * File-session equivalent of Laravel's DatabaseSessionHandler purge:
     * remove every session file for the current user except the current one.
     * Auth::logoutOtherDevices already invalidates their auth, this just
     * keeps the sessions listing clean.
     */
    private function deleteOtherSessionRecords(Request $request): void
    {
        $driver = config('session.driver');
        if ($driver !== 'file') return;

        $dir       = base_path('storage/framework/sessions');
        if (! is_dir($dir)) return;
        $userId    = (int) Auth::id();
        $guardKey  = 'login_web_'.sha1(\Illuminate\Auth\SessionGuard::class);
        $currentId = $request->session()->getId();

        foreach (@scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === $currentId) continue;
            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            if (! is_file($path)) continue;
            $raw = @file_get_contents($path);
            if ($raw === false || $raw === '') continue;
            $decoded = @unserialize($raw);
            if (! is_array($decoded)) continue;
            if (! isset($decoded[$guardKey]) || (int) $decoded[$guardKey] !== $userId) continue;
            @unlink($path);
        }
    }

    private function translations(): array
    {
        return [
            'title'    => __('browser_sessions.title') ?: 'Browser Sessions',
            'intro'    => __('browser_sessions.intro') ?: 'Manage and log out your active sessions on other browsers and devices.',
            'note'     => __('browser_sessions.note') ?: 'If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.',
            'this_device' => __('browser_sessions.this_device') ?: 'This device',
            'last_active' => __('browser_sessions.last_active') ?: 'Last active',
            'logout_others' => __('browser_sessions.logout_others') ?: 'Log Out Other Browser Sessions',
            'confirm_password' => __('browser_sessions.confirm_password') ?: 'Please enter your password to confirm.',
            'password_placeholder' => __('browser_sessions.password_placeholder') ?: 'Password',
            'cancel'   => __('levels.cancel') ?: 'Cancel',
            'confirm'  => __('browser_sessions.confirm') ?: 'Log Out Other Sessions',
            'logged_out'  => __('browser_sessions.logged_out') ?: 'Other browser sessions logged out.',
        ];
    }
}
