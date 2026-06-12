<?php

namespace App\Http\Requests\Merchant;

use App\Models\Backend\Merchant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user  = Merchant::findOrFail($this->id);
        $userID = $user->user_id;
        return [
            'name'                  => ['required','string','max:191'],
            'business_name'         => ['required','string'],
            'mobile'                => ['required','numeric','digits_between:11,14'],
            'hub'                   => ['required','numeric'],
            'status'                => ['required','numeric'],
            'address'               => ['required','string','max:191'],
            'payment_period'        => ['numeric'],
            // Geography coverage. At least one country always required.
            // Cities only required when covers_all_cities is unchecked.
            'country_ids'           => ['required','array','min:1'],
            'country_ids.*'         => ['integer','exists:countries,id'],
            'covers_all_cities'     => ['nullable','boolean'],
            'city_ids'              => ['nullable','array',
                                        function ($attr, $value, $fail) {
                                            if (! $this->boolean('covers_all_cities') && empty($value)) {
                                                $fail(trans('merchant.cities_required_unless_all'));
                                            }
                                        }],
            'city_ids.*'            => ['integer','exists:cities,id'],
        ];
    }
 
    public function withValidator($validator)
    { 
        $validator->after(function ($validator) {
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('email', trans('validation.attributes.email'));
                }
        });
    }

    private function userUniqueCheck()
    { 
        
        $queryArray['company_id']               = settings()->id; 
        $data = [];
        $data['email']   = $this->email;
        $data['mobile']  = $this->mobile;

        $user         = User::where($queryArray)->where(function($query)use($data){
            $query->where('email',$data['email']);
            $query->orWhere('mobile', $data['mobile']);
        })->where(function($query){
            $query->whereHas('merchant',function($query){ 
                $query->whereNot('id',$this->id);
            });
        })->first(); 
        if (blank($user)) {
            return false;
        }
        return true;
    }



}
