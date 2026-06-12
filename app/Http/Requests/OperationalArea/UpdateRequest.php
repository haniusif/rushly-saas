<?php

namespace App\Http\Requests\OperationalArea;

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
            'id'     => ['required', 'numeric', 'exists:operational_areas,id'],
            'name'   => ['required', 'string', 'max:191'],
            'code'   => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'numeric'],
        ];
    }
}
