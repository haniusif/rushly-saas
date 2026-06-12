<?php

namespace App\Http\Requests\DeliveryMan;

use App\Models\Backend\DeliveryMan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class DeliveryManRequest extends FormRequest
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
        if (Request::input('id')) {

            $user   = DeliveryMan::findOrFail(Request::input('id'));
            $userID = $user->user_id;

            $email    = ['required', 'email', 'string', Rule::unique("users", "email")->ignore($userID)];
            $mobile   = ['required', 'numeric','digits_between:11,14', Rule::unique("users", "mobile")->ignore($userID)];
            $password = ['nullable'];
        } else {
            $email    = ['required', 'email', 'string', 'unique:users,email'];
            $mobile   = ['required', 'numeric','digits_between:11,14', 'unique:users,mobile'];
            $password = ['required', 'min:6'];
        }

        $driverType = Request::input('driver_type');

        return [
            // Core
            'name'                       => ['required', 'string','max:191'],
            'name_en'                    => ['nullable', 'string', 'max:191'],
            'email'                      => $email,
            'password'                   => $password,
            'mobile'                     => $mobile,
            'alt_mobile'                 => ['nullable', 'string', 'max:50'],
            'gender'                     => ['nullable', 'in:male,female'],
            'dob'                        => ['nullable', 'date'],
            'nationality'                => ['nullable', 'string', 'max:100'],

            // ID
            'id_type'                    => ['nullable', 'in:national_id,iqama'],
            'id_number'                  => ['nullable', 'string', 'max:50'],
            'id_expiry'                  => ['nullable', 'date'],
            'id_image_id'                => 'nullable|image|mimes:jpeg,png,jpg|max:5098',

            // Address
            'address'                    => ['required','string', 'max:200'],
            'district'                   => ['nullable', 'string', 'max:191'],
            'short_national_address'     => ['nullable', 'string', 'max:50'],

            // Employment
            'driver_type'                => ['required', 'in:freelancer,outsourced,company_courier'],
            'employee_number'            => [$driverType === 'company_courier' ? 'required' : 'nullable', 'string', 'max:50'],
            'joining_date'               => ['nullable', 'date'],
            'contract_end_date'          => ['nullable', 'date', 'after_or_equal:joining_date'],
            'status'                     => ['required', 'numeric'],
            'hub_id'                     => ['required', 'numeric'],
            'direct_manager_id'          => ['nullable', 'numeric'],
            'operational_area_id'        => ['nullable', 'numeric'],
            'supplier_company_id'        => [$driverType === 'outsourced' ? 'required' : 'nullable', 'numeric'],

            // License
            'license_number'             => ['nullable', 'string', 'max:50'],
            'license_expiry'             => ['nullable', 'date'],
            'iqama_expiry'               => ['nullable', 'date'],

            // Bank (freelancers)
            'bank_account_no'            => [$driverType === 'freelancer' ? 'nullable' : 'nullable', 'string', 'max:50'],
            'iban'                       => [$driverType === 'freelancer' ? 'nullable' : 'nullable', 'string', 'max:50'],

            // Charges
            'delivery_charge'            => ['nullable', 'numeric'],
            'pickup_charge'              => ['nullable', 'numeric'],
            'return_charge'              => ['nullable', 'numeric'],
            'opening_balance'            => ['nullable', 'numeric'],

            // Uploads
            'image_id'                   => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'driving_license_image_id'   => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'iqama_image_id'             => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'contract_image_id'          => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'promissory_note_image_id'   => 'nullable|image|mimes:jpeg,png,jpg|max:5098',

            'salary'                     => ['nullable', 'numeric'],
        ];
    }

    public function attributes()
    {
        return [
            'name'                       => trans('validation.attributes.name'),
            'status'                     => trans('validation.attributes.status'),
            'email'                      => trans('validation.attributes.email'),
            'mobile'                     => trans('validation.attributes.phone'),
            'address'                    => trans('validation.attributes.address'),
            'hub_id'                     => trans('validation.attributes.hub_id'),
            'opening_balance'            => trans('validation.attributes.opening_balance'),
            'delivery_charge'            => trans('validation.attributes.delivery_charge'),
            'pickup_charge'              => trans('validation.attributes.pickup_charge'),
            'return_charge'              => trans('validation.attributes.return_charge'),
            'image_id'                   => trans('validation.attributes.image_id'),
            'driving_license_image_id'   => trans('validation.attributes.driving_license_image_id'),
        ];
    }


}
