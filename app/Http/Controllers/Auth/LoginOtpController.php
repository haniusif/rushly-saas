<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginOtpController extends Controller
{
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const MAX_RESENDS         = 3;

    /**
     * Render the 6-digit code entry page. Only reachable when the previous
     * password-step stashed an OTP payload in the session; anyone landing
     * here cold gets bounced back to /login.
     */
    public function show(Request $request)
    {
        if (! $this->hasOtpPayload($request)) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        return view('auth.login-otp');
    }

    /**
     * Verify the submitted code. Hash-compare against the payload stashed in
     * the session; on success finish the sign-in (Auth::login with the
     * remembered flag) and honor intended URL like a normal login.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $payload = $request->session()->get('login_otp');
        if (! $this->payloadIsValid($payload)) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        if (($payload['attempts'] ?? 0) >= self::MAX_VERIFY_ATTEMPTS) {
            $request->session()->forget('login_otp');
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_throttle', ['seconds' => 300])]);
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('login_otp');
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_expired')]);
        }

        if (! Hash::check((string) $request->input('code'), $payload['hash'])) {
            $payload['attempts'] = ($payload['attempts'] ?? 0) + 1;
            $request->session()->put('login_otp', $payload);
            return back()->withErrors(['code' => __('auth.login_otp_invalid')]);
        }

        $user = User::find((int) $payload['user_id']);
        if (! $user) {
            $request->session()->forget('login_otp');
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        $remember = (bool) ($payload['remember'] ?? false);
        Auth::login($user, $remember);
        $request->session()->forget('login_otp');

        $request->session()->regenerate();
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended('/');
    }

    /**
     * Regenerate the code and re-send. Capped at self::MAX_RESENDS per session
     * to prevent mail flooding — the counter lives on the same session payload
     * so it can't be wiped by opening a new tab.
     */
    public function resend(Request $request)
    {
        $payload = $request->session()->get('login_otp');
        if (! $this->payloadIsValid($payload)) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        if (($payload['resends'] ?? 0) >= self::MAX_RESENDS) {
            return back()->withErrors([
                'code' => __('auth.login_otp_throttle', ['seconds' => 300]),
            ]);
        }

        $user = User::find((int) $payload['user_id']);
        if (! $user || empty($user->email)) {
            $request->session()->forget('login_otp');
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        $code       = LoginController::currentOtpCode();
        $ttlMinutes = 5;

        $payload['hash']       = Hash::make($code);
        $payload['expires_at'] = now()->addMinutes($ttlMinutes)->timestamp;
        $payload['attempts']   = 0;
        $payload['resends']    = ($payload['resends'] ?? 0) + 1;
        $request->session()->put('login_otp', $payload);

        try {
            Mail::to($user->email)->send(new LoginOtpMail($code, $ttlMinutes, $user->name ?: 'there'));
        } catch (\Throwable $e) {
            \Log::error('Login OTP resend mail failed: '.$e->getMessage(), ['user_id' => $user->id]);
        }

        return back()->with('status', __('auth.login_otp_resent'));
    }

    private function hasOtpPayload(Request $request): bool
    {
        return $this->payloadIsValid($request->session()->get('login_otp'));
    }

    private function payloadIsValid($payload): bool
    {
        return is_array($payload)
            && ! empty($payload['user_id'])
            && ! empty($payload['hash'])
            && ! empty($payload['expires_at']);
    }
}
