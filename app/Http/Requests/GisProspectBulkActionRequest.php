<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GisProspectBulkActionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('records') && is_array($this->input('ids'))) {
            $this->merge([
                'records' => collect($this->input('ids'))
                    ->map(fn ($id) => 'gis_enquiry:'.(int) $id)
                    ->all(),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1', 'max:100'],
            'records.*' => ['required', 'string', 'distinct', 'regex:/^(gis_enquiry|fair_funnel):[1-9][0-9]*$/'],
            'action' => ['required', Rule::in(['delete', 'restore', 'assign', 'status'])],
            'user_id' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['required_if:action,status', 'nullable', Rule::in(EnquiryStatus::values())],
        ];
    }
}
