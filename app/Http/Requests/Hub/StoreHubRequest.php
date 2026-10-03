<?php

namespace App\Http\Requests\Hub;

use Illuminate\Foundation\Http\FormRequest;

class StoreHubRequest extends FormRequest
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
        return [
            'name'    => ['required','string','unique:hubs','max:191'],
            'phone'   => ['required','numeric','digits_between:11,14'],
            'address' => ['required','string','max:191'],
            // Nullable: existing hubs predate this field and an operator
            // should not be blocked from saving until it is filled in.
            'city_id' => ['nullable','integer','exists:cities,id'],
        ];
    }
}
