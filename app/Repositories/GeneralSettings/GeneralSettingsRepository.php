<?php
namespace App\Repositories\GeneralSettings;

use App\Enums\UserType;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Upload;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class GeneralSettingsRepository implements GeneralSettingsInterface{

    public function all(){

        $row =  GeneralSettings::with('rxlogo','rxfavicon')->where(function($query){
            if(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN):
                $query->where('id',Auth::user()->company_id);
            else:
                $query->where('id',1);
            endif;
        })->first();
        return $row;
    }

    public function update($request){

        $row               = GeneralSettings::with('rxlogo','rxfavicon')->where(function($query){
            if(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN):
                $query->where('id',Auth::user()->company_id);
            else:
                $query->where('id',1);
            endif;
        })->first();
        $row->name         = $request->name;
        $row->phone        = $request->phone;
        $row->email        = $request->email;
        $row->address      = $request->address;
        $row->currency     = $request->currency;
        $row->copyright    = $request->copyright;
        $row->par_track_prefix     = Str::upper($request->par_track_prefix);
        $row->invoice_prefix       = Str::upper($request->invoice_prefix);
        $row->show_landing_page    = $request->boolean('show_landing_page');
        if($request->primary_color):
            $row->primary_color        = $request->primary_color;
        endif;
        if($request->text_color):
            $row->text_color           = $request->text_color;
        endif;
        if (in_array($request->input('login_layout'), ['split','centered','fullbleed'], true)) {
            $row->login_layout = $request->input('login_layout');
        }

        // Timezone: NULL clears back to the app default; anything else must
        // be a real identifier from PHP's zoneinfo list to avoid poisoning
        // date_default_timezone_set() in the SetTenantTimezone middleware.
        if ($request->has('timezone')) {
            $tz = trim((string) $request->input('timezone'));
            if ($tz === '') {
                $row->timezone = null;
            } elseif (in_array($tz, timezone_identifiers_list(), true)) {
                $row->timezone = $tz;
            }
        }

        // Extended theme defaults (inherited by every merchant on this tenant unless
        // they set their own override). Each field can be cleared by passing "".
        foreach (['sidebar_color','sidebar_text_color','topbar_color','topbar_text_color','accent_color'] as $field) {
            if (! $request->has($field)) continue;
            $v = trim((string) $request->input($field));
            if ($v === '') {
                $row->{$field} = null;
            } elseif (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $v)) {
                $row->{$field} = strtolower($v);
            }
        }
        $enums = [
            'sidebar_style' => ['dark','light','brand'],
            'font_family'   => ['inter','cairo','tajawal','roboto','system'],
            'border_radius' => ['sharp','default','rounded'],
            'density'       => ['dense','comfortable'],
        ];
        foreach ($enums as $field => $allowed) {
            if (! $request->has($field)) continue;
            $v = trim((string) $request->input($field));
            if ($v === '') {
                $row->{$field} = null;
            } elseif (in_array($v, $allowed, true)) {
                $row->{$field} = $v;
            }
        }

        if(isset($request->logo) && $request->logo != null)
        {
            $row->logo = $this->file($row->logo, $request->logo);
        }
        if(isset($request->light_logo) && $request->light_logo != null)
        {
            $row->light_logo = $this->file($row->light_logo, $request->light_logo);
        }
        if(isset($request->favicon) && $request->favicon != null)
        {
            $row->favicon = $this->file($row->favicon, $request->favicon);
        }
        if(isset($request->login_bg) && $request->login_bg != null)
        {
            $row->login_bg = $this->file($row->login_bg, $request->login_bg);
        }
        // Explicit clear: form posts login_bg_clear=1 to remove the current image.
        if ($request->boolean('login_bg_clear')) {
            $row->login_bg = null;
        }
        $row->save();
        return $row;

    }

    /**
     * Store an uploaded logo / favicon and return the resulting upload id.
     *
     * Multi-tenant safety: we ALWAYS create a fresh `uploads` row per change
     * and NEVER mutate or unlink the previous file. Historically this method
     * did `Upload::find($id)` and overwrote the row + deleted its file, but
     * because `general_settings.favicon` was seeded to the same upload id on
     * every tenant, tenant A's upload would clobber every other tenant's
     * favicon. Leaving the old row as an orphan is safe (GC can clean up
     * later) and cheap.
     *
     * @param  mixed  $image_id  Kept for signature compatibility; ignored now.
     * @param  \Illuminate\Http\UploadedFile|null $image
     * @return int|false  New upload id on success, false on failure.
     */
    public function file($image_id = '', $image)
    {
        try {
            if (blank($image)) return false;

            $destinationPath = public_path('uploads/settings');
            if (! is_dir($destinationPath)) {
                @mkdir($destinationPath, 0755, true);
            }
            $profileImage = date('YmdHis') . random_int(1000, 9999) . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $profileImage);

            $upload = new Upload();
            $upload->original = 'uploads/settings/' . $profileImage;
            $upload->save();

            return $upload->id;
        } catch (\Throwable $e) {
            \Log::warning('general-settings.upload_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

}
