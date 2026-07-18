<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Laravel\Socialite\Facades\Socialite;
class SocialLoginController extends Controller
{
    protected $merchantRepo;
    public function __construct(MerchantInterface $merchantRepo,SocialLoginSettingsInterface $repo)
    {
        $this->merchantRepo = $merchantRepo;
        $this->repo         = $repo;
    }
    public function socialRedirect($social){

        if($social == 'google'):
            if(globalSettings('google_status') != Status::ACTIVE):
                Toastr::error('Google login is not enabled.','error');
                return redirect()->back();
            endif;
            \Config([
                'services.google.client_id'        => globalSettings('google_client_id'),
                'services.google.client_secret'    => globalSettings('google_client_secret'),
                'services.google.redirect'         => url('google/login')
            ]);
 
            return Socialite::driver('google')->redirect();

        elseif($social == 'facebook'):

            if(globalSettings('facebook_status') != Status::ACTIVE):
                Toastr::error('Facebook login is not enabled.','error');
                return redirect()->back();
            endif;
            \Config([
                'services.facebook.client_id'        => globalSettings('facebook_client_id'),
                'services.facebook.client_secret'    => globalSettings('facebook_client_secret'),
                'services.facebook.redirect'         => url('facebook/login')
            ]);

            return Socialite::driver('facebook')->redirect();
        endif;

        Toastr::error('parcel.error_msg',__('message.error'));
        return redirect()->back();
    }
    public function authGoogleLogin(Request $request){
       try {

        \Config([
            'services.google.client_id'        => globalSettings('google_client_id'),
            'services.google.client_secret'    => globalSettings('google_client_secret'),
            'services.google.redirect'         => url('google/login')
        ]);

        $user      = Socialite::driver('google')->user();
        $existUser = User::where('google_id',$user->id)->first();
        if($existUser):
            Auth::login($existUser);
            return redirect('/');
        else:
            $merchantUser = $this->merchantRepo->socialSignupStore($user,'google');
            if($merchantUser):
                Auth::login($merchantUser);
                return redirect('/');
            else:
                Toastr::error('parcel.error_msg',__('message.error'));
                return redirect()->back();
            endif;
        endif;

       } catch (\Throwable $th) {
           Toastr::error('parcel.error_msg',__('message.error'));
           return redirect()->back();
       }
    }
    public function authFacebookLogin(){
        try {

            \Config([
                'services.facebook.client_id'        => globalSettings('facebook_client_id'),
                'services.facebook.client_secret'    => globalSettings('facebook_client_secret'),
                'services.facebook.redirect'         => url('facebook/login')
            ]);
            $user        = Socialite::driver('facebook')->user();

            $existUser   = User::where('facebook_id',$user->id)->first();
            if($existUser):
                Auth::login($existUser);
                return redirect('/');
            else:
                $merchantUser = $this->merchantRepo->socialSignupStore($user,'facebook');
                if($merchantUser):
                    Auth::login($merchantUser);
                    return redirect('/');
                else:
                    Toastr::error('parcel.error_msg',__('message.error'));
                    return redirect()->back();
                endif;
            endif;

        } catch (\Throwable $th) {

            Toastr::error('parcel.error_msg',__('message.error'));
            return redirect()->back();
        }
    }

    public function socialLoginSettingsIndex()
    {
        return \Inertia\Inertia::render('Admin/SocialLoginSettings/Index', [
            'facebook' => [
                'client_id'     => (string) (globalSettings('facebook_client_id') ?? ''),
                'client_secret' => (string) (globalSettings('facebook_client_secret') ?? ''),
                'status'        => (int) (globalSettings('facebook_status') ?? 0) === Status::ACTIVE,
            ],
            'google' => [
                'client_id'     => (string) (globalSettings('google_client_id') ?? ''),
                'client_secret' => (string) (globalSettings('google_client_secret') ?? ''),
                'status'        => (int) (globalSettings('google_status') ?? 0) === Status::ACTIVE,
            ],
            'permissions' => [
                'update' => hasPermission('social_login_settings_update'),
            ],
            'urls' => [
                'submit_facebook' => route('social.login.settings.update', 'facebook'),
                'submit_google'   => route('social.login.settings.update', 'google'),
            ],
            't' => [
                'title'         => __('menus.social_login_settings') ?: 'Social login settings',
                'facebook'      => __('levels.facebook') ?: 'Facebook',
                'google'        => __('levels.google') ?: 'Google',
                'app_id'        => __('levels.app_id') ?: 'App ID',
                'app_secret'    => __('levels.app_secret') ?: 'App secret',
                'client_id'     => __('levels.client_id') ?: 'Client ID',
                'client_secret' => __('levels.client_secret') ?: 'Client secret',
                'status'        => __('levels.status') ?: 'Status',
                'enabled'       => __('levels.active') ?: 'Enabled',
                'disabled'      => __('levels.inactive') ?: 'Disabled',
                'save'          => __('levels.save_change') ?: 'Save changes',
                'fb_hint'       => 'From Facebook Developers → App → Settings → Basic. Toggle Status off to hide the Facebook button on the login screen.',
                'google_hint'   => 'From Google Cloud Console → APIs & Services → Credentials. Toggle Status off to hide the Google button on the login screen.',
            ],
        ]);
    }

    public function socialLoginSettingsUpdate(Request $request,$social){

        if($this->repo->update($request,$social)):
            Toastr::success(__('parcel.settings_update_success'),__('message.success'));
            return redirect()->route('social.login.settings.index');
        else:
            Toastr::error('parcel.error_msg',__('message.error'));
            return redirect()->back();
        endif;
    }


}
