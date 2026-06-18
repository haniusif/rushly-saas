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
        $row->save();
        return $row;

    }

    public function file($image_id = '', $image)
    {
         
        try {
            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/settings');
                $profileImage          = date('YmdHis') .random_int(1000,9999). "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/settings/'.$profileImage;
            }
            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                if(file_exists($upload->original))
                {
                    unlink($upload->original);
                }
            }
            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;
        }
        catch (\Exception $e) {
            return false;
        }
    }

}
