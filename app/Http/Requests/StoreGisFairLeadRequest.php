<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGisFairLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->filled('website')) {
            return ['website' => ['nullable', 'string', 'max:255']];
        }

        return [
            'firstName' => ['required', 'string', 'max:50'],
            'lastName' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:100'],
            'company' => ['required', 'string', 'max:150'],
            'businessType' => ['required', Rule::in(config('gis_fair.business_types'))],
            'stores' => ['required', 'integer', 'min:1', 'max:100000'],
            'phoneIso' => ['required', 'string', 'size:2', Rule::in(array_keys(config('gis_fair.countries')))],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/'],
            'country' => ['required', 'string', 'max:80'],
            'currentSystem' => ['required', Rule::in(config('gis_fair.current_systems'))],
            'interests' => ['required', 'array', 'min:1', 'max:6'],
            'interests.*' => ['required', 'distinct', Rule::in(config('gis_fair.interests'))],
            'consent' => ['sometimes', 'boolean'],
            'privacyAgree' => ['required', 'accepted'],
            'source' => ['nullable', Rule::in(config('gis_fair.sources'))],
            'eventCode' => ['nullable', 'string', 'max:64'],
            'trackingToken' => ['nullable', 'uuid'],
            'renderedAt' => ['nullable', 'date'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('website') || $validator->errors()->has('phoneIso')) {
                return;
            }

            $country = config('gis_fair.countries.'.strtoupper((string) $this->input('phoneIso')));
            if (! $country) {
                return;
            }

            $digits = preg_replace('/\D+/', '', (string) $this->input('phone'));
            $trunk = array_key_exists('trunk', $country) ? $country['trunk'] : '0';
            $allowed = $country['lengths'];
            if ($trunk !== '') {
                $allowed = array_values(array_unique(array_merge($allowed, array_map(fn ($length) => $length - strlen($trunk), $allowed))));
            }

            if (! in_array(strlen($digits), $allowed, true)) {
                $validator->errors()->add('phone', 'The phone number length is not valid for the selected country.');
            }

            if (strcasecmp((string) $this->input('country'), $country['name']) !== 0) {
                $validator->errors()->add('country', 'The country does not match the selected phone country.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $data = $this->sanitize($this->all());
        $data['email'] = strtolower((string) ($data['email'] ?? ''));
        $data['phoneIso'] = strtoupper((string) ($data['phoneIso'] ?? ''));
        $data['source'] = $data['source'] ?? 'gis-fair-funnel';
        $data['consent'] = filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->replace($data);
    }

    private function sanitize(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($value));
            } elseif (is_array($value)) {
                $input[$key] = $this->sanitize($value);
            }
        }

        return $input;
    }
}
