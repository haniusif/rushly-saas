<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            // Uniqueness is per TENANT, not global. Roles are owned by a
            // company_id, and every tenant now carries the same standard set
            // (Finance, Dispatcher, …), so a bare unique:roles would reject a
            // tenant creating a role name another tenant already uses.
            'name'   => [
                'required', 'string', 'max:60',
                \Illuminate\Validation\Rule::unique('roles')->where(
                    fn ($q) => $q->where('company_id', optional(settings())->id)
                ),
            ],
            'status' => ['required','numeric'],
        ];
    }
}
