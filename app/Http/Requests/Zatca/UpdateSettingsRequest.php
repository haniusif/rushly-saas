<?php

namespace App\Http\Requests\Zatca;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seller_name_en'    => ['required', 'string', 'max:200'],
            'seller_name_ar'    => ['required', 'string', 'max:200'],
            'vat_number'        => ['required', 'digits:15'],
            'cr_number'         => ['nullable', 'string', 'max:30'],
            'address_street_en' => ['nullable', 'string', 'max:255'],
            'address_street_ar' => ['nullable', 'string', 'max:255'],
            'building_number'   => ['nullable', 'string', 'max:10'],
            'district_en'       => ['nullable', 'string', 'max:100'],
            'district_ar'       => ['nullable', 'string', 'max:100'],
            'city_en'           => ['nullable', 'string', 'max:100'],
            'city_ar'           => ['nullable', 'string', 'max:100'],
            'postal_code'       => ['nullable', 'string', 'max:10'],
            'country_code'      => ['nullable', 'string', 'size:2'],
            'vat_rate'          => ['required', 'numeric', 'between:0,100'],
            'currency'          => ['required', 'string', 'size:3'],
            'mode'              => ['required', 'in:sandbox,production'],
            'enabled'           => ['boolean'],
            'auto_generate'     => ['boolean'],
            'invoice_prefix'    => ['nullable', 'string', 'max:20'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();
        $data['enabled']       = $this->boolean('enabled');
        $data['auto_generate'] = $this->boolean('auto_generate');
        $data['vat_number']    = preg_replace('/\D/', '', (string) $data['vat_number']);
        $data['country_code']  = strtoupper((string) ($data['country_code'] ?? 'SA'));
        return $data;
    }

    public function messages(): array
    {
        return [
            'vat_number.digits' => 'Saudi VAT number must be exactly 15 digits (starts and ends with 3).',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $vat = preg_replace('/\D/', '', (string) $this->input('vat_number'));
            if (strlen($vat) === 15 && (substr($vat, 0, 1) !== '3' || substr($vat, -1) !== '3')) {
                $v->errors()->add('vat_number', 'Saudi VAT number must start and end with digit "3".');
            }
        });
    }
}
