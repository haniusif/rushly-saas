<?php

namespace App\Http\Requests\ChildCompany;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:191'],
            'domain'       => ['required', 'unique:domains,domain_name', 'regex:/(^[a-zA-Z]+[a-zA-Z0-9\-]*$)/u'],
            'currency'     => ['required', 'string', 'max:16'],
            'plan_id'      => ['required', 'numeric', 'exists:plans,id'],
            'address'      => ['nullable', 'string', 'max:191'],

            // owner user information
            'name'         => ['required', 'string', 'max:191'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:6'],
            'mobile'       => ['required', 'string', 'max:32', 'unique:users,mobile'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Defaults so CompanyRepository::store() (built for the super-admin
        // form) has all the fields it reads without erroring on nulls.
        $this->merge([
            'status'           => $this->status ?? \App\Enums\Status::ACTIVE,
            'par_track_prefix' => $this->par_track_prefix ?? 'CH',
            'invoice_prefix'   => $this->invoice_prefix ?? 'INV',
            'nid_number'       => $this->nid_number,
            'joining_date'     => $this->joining_date ?? now()->toDateString(),
            'designation_id'   => $this->designation_id,
            'department_id'    => $this->department_id,
        ]);
    }
}
