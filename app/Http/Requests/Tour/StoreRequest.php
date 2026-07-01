<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'key'           => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_.-]+$/i'],
            'module'        => ['nullable', 'string', 'max:60'],
            'title'         => ['required', 'string', 'max:191'],
            'description'   => ['nullable', 'string'],
            'role_scope'    => ['nullable', 'array'],
            'role_scope.*'  => ['integer', 'min:1', 'max:6'],
            'version'       => ['nullable', 'integer', 'min:1'],
            'is_active'     => ['nullable', 'boolean'],
            'auto_start'    => ['nullable', 'boolean'],
            'trigger_route' => ['nullable', 'string', 'max:191'],
            'steps'         => ['required', 'array', 'min:1'],
            'steps.*.target'            => ['required', 'array'],
            'steps.*.target.type'       => ['required', 'in:data-tour,selector,route-name'],
            'steps.*.target.value'      => ['required', 'string', 'max:191'],
            'steps.*.placement'         => ['nullable', 'in:top,bottom,start,end,auto'],
            'steps.*.spotlight_padding' => ['nullable', 'integer', 'min:0', 'max:64'],
            'steps.*.translations'      => ['required', 'array'],
            'steps.*.translations.en.title' => ['required_without:steps.*.translations.ar.title', 'string', 'max:191'],
            'steps.*.translations.en.body'  => ['nullable', 'string'],
            'steps.*.translations.ar.title' => ['nullable', 'string', 'max:191'],
            'steps.*.translations.ar.body'  => ['nullable', 'string'],
            'steps.*.action'            => ['nullable', 'array'],
        ];
    }
}
