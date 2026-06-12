<?php

namespace App\Http\Requests\OperationalArea;

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
            'name'   => ['required', 'string', 'max:191'],
            'code'   => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'numeric'],
        ];
    }
}
