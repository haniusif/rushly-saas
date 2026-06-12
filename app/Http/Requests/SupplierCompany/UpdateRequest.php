<?php

namespace App\Http\Requests\SupplierCompany;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id'            => ['required', 'numeric', 'exists:supplier_companies,id'],
            'name'          => ['required', 'string', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status'        => ['required', 'numeric'],
        ];
    }
}
