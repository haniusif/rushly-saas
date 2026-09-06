<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

  
    // Auth login 
    public function login(Request $request)
    {
        
        // Active Remember me 24 houre
        if($request->remember != null)
        {
            Cookie::queue('useremail',$request->email,1440);
            Cookie::queue('userpassword',$request->password,1440);
        }
        else
        {
            Cookie::queue(Cookie::forget('useremail'));
            Cookie::queue(Cookie::forget('userpassword'));
        }
        
        $this->validateLogin($request);

        $user    = User::where(function($query)use ($request){
            $query->where('email',$request->email);
            $query->orWhere('mobile',$request->email);
        })->first();
        
        if(tenant()):
            if($user && $user->user_type == UserType::SUPER_ADMIN):
                return $this->sendFailedLoginResponse($request);
            elseif($user && $user->company_id != settings()->id): 
                return $this->sendFailedLoginResponse($request);
            endif;
           
        else: 
         
            if($user && $user->user_type != UserType::SUPER_ADMIN): 
                return redirect()->to(scheme_name($user->tenantDetails->domains[0]->domain));
                // return $this->sendFailedLoginResponse($request);
            endif;
        endif;
         
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $authed = $this->guard()->user();

            // Two-step login (features.login_otp): after a valid password,
            // Admin/SuperAdmin users get a 6-digit code emailed to them
            // instead of being fully signed in. Merchants/deliverymen skip.
            if ($this->requiresLoginOtp($authed)) {
                return $this->challengeWithOtp($request, $authed);
            }

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
        

    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Where to send the user after sign-out. Default trait sends to '/' (tenant home),
     * but for the merchant portal we want the login screen.
     */
    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }

    /**
     * Called by AuthenticatesUsers::sendLoginResponse right after a successful
     * login. First-login stamp is what drives the onboarding tour auto-start.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user && \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_login_at') && $user->first_login_at === null) {
            // Mark AFTER the tour engine gets to see first_login=true on the
            // very next page load; we stamp here so subsequent logins skip
            // the welcome modal.
            $user->forceFill(['first_login_at' => now()])->save();
        }
    }

    /**
     * Branded login screen. Without a slug the view shows the tenant brand; with a
     * slug matching a `merchant_unique_id` it overlays that merchant's colors/logo
     * pre-auth so admins can hand out merchant-branded login URLs.
     */
    public function showLoginForm(?string $slug = null)
    {
        $brand = loginBrand($slug);
        $layout = $brand['login_layout'] ?? 'split';
        $view = match ($layout) {
            'centered'  => 'auth.login-centered',
            'fullbleed' => 'auth.login-fullbleed',
            default     => 'auth.login',
        };
        return view($view, [
            'loginBrand' => $brand,
            'loginSlug'  => $slug,
        ]);
    }

    /**
     * True when this user must complete the email-OTP challenge before the
     * session is considered fully signed in. Gated by features.login_otp and
     * scoped to staff (Admin, SuperAdmin) only.
     */
    protected function requiresLoginOtp($user): bool
    {
        if (! $user) {
            return false;
        }
        if (! config('features.login_otp')) {
            return false;
        }
        return in_array((int) $user->user_type, [UserType::ADMIN, UserType::SUPER_ADMIN], true);
    }

    /**
     * Log the user back out immediately, park their id + remember-me choice
     * in the session together with the hashed OTP, generate the code, and
     * email it. Storage is session-based because on tenant subdomains the
     * default cache driver (file) can't be tagged by Stancl Tenancy's cache
     * bootstrapper — the session bypasses that entire path.
     */
    protected function challengeWithOtp(Request $request, $user)
    {
        if (empty($user->email)) {
            Auth::logout();
            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('auth.login_otp_session_lost')]);
        }

        $code       = self::currentOtpCode();
        $ttlMinutes = 5;

        Auth::logout();
        $request->session()->put('login_otp', [
            'user_id'    => $user->id,
            'remember'   => (bool) $request->boolean('remember'),
            'hash'       => Hash::make($code),
            'expires_at' => now()->addMinutes($ttlMinutes)->timestamp,
            'attempts'   => 0,
            'resends'    => 0,
        ]);

        try {
            Mail::to($user->email)->send(new LoginOtpMail($code, $ttlMinutes, $user->name ?: 'there'));
        } catch (\Throwable $e) {
            \Log::error('Login OTP mail failed: '.$e->getMessage(), ['user_id' => $user->id]);
        }

        return redirect()->route('login.otp.show')->with('status', __('auth.login_otp_sent'));
    }

    /**
     * The 6-digit code. Format: MM DD HH (minutes, day, hour) in the app's
     * configured timezone (`config('app.timezone')`). Deterministic by
     * design — the mailed code is still sent, but staff can also compute
     * it from the clock. The session stores the hash generated at
     * password-step time, so a code minted at :21 stays valid for its TTL
     * even after the wall clock rolls to :22.
     *
     * WARNING: predictable OTPs are barely a second factor. Anyone who
     * knows the format and the server's clock at generation time can
     * produce a valid code without needing email access. Swap this out
     * for `random_int(...)` if you decide the shared-secret tradeoff
     * isn't acceptable.
     */
    public static function currentOtpCode(): string
    {
        // TEMP: fixed dev OTP for pilot access. Every staff sign-in accepts
        // 123456 regardless of the clock. Restore `now()->format('idH')` to
        // return to the time-based deterministic code.
        return '123456';
    }

    protected function credentials(Request $request)
    {
         
        if(tenant()): 
          
            if(is_numeric($request->get('email')))
            {
                return [ 
                        'mobile'        => $request->get('email'),
                        'company_id'    => settings()->id,
                        'password'      => $request->get('password'),
                        'status'        => '1', 
                        'verification_status' => '1'
                     ];
            }
            return [
                    'email'               => $request->get('email'),
                    'company_id'          => settings()->id,
                    'password'            => $request->get('password'),
                    'status'              => '1',
                    'verification_status' => '1'
                ];
         
        else:  
            if(is_numeric($request->get('email')))
            {
                return ['mobile' => $request->get('email'),'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1' ];
            }
            return ['email' => $request->get('email'),'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1'];
        endif;
    }

    /**
     * Tell the user WHY the login failed.
     *
     * This one method is the exit for four different failures: a wrong
     * password, a disabled/unverified account, a user belonging to a different
     * company, and a super admin signing in on a tenant host. All four used to
     * render `auth.failed`, and `auth.failed` had been reworded to "Your
     * account is currently inactive. Please contact the administrator" — so a
     * simple typo in a password told the user they had been deactivated and
     * sent them to their admin instead of to the password reset.
     *
     * `auth.failed` is back to the standard credentials wording. The inactive
     * message now lives under `auth.inactive` and is only used when a matching
     * account genuinely IS disabled.
     *
     * Note credentials() pins status = 1 and verification_status = 1, so a
     * disabled account never authenticates regardless of the message shown —
     * this changes only what the user is told, never who gets in.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $identifier = $request->get($this->username());

        $user = User::where(function ($query) use ($identifier) {
            $query->where('email', $identifier)->orWhere('mobile', $identifier);
        })->first();

        // Only claim "inactive" when we can actually see a disabled account for
        // that identifier. Anything else — no such user, wrong password, wrong
        // tenant — is a credentials failure, and saying so leaks nothing beyond
        // what the standard Laravel message already does.
        $isDisabled = $user
            && ((string) $user->status !== '1' || (string) $user->verification_status !== '1');

        throw ValidationException::withMessages([
            $this->username() => [__($isDisabled ? 'auth.inactive' : 'auth.failed')],
        ]);
    }
}
