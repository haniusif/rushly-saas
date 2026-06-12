<?php

namespace App\Http\Requests\SupplierCompany;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'          => ['required', 'string', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status'        => ['required', 'numeric'],
        ];
    }
}
