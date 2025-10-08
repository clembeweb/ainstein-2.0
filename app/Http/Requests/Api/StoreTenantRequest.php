<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->is_super_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants'],
            'subdomain' => ['nullable', 'string', 'max:100', 'unique:tenants'],
            'plan_type' => ['required', 'string', 'in:free,pro,enterprise'],
            'tokens_monthly_limit' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
            'theme_config' => ['sometimes', 'array'],
            'brand_config' => ['sometimes', 'array'],
            'features' => ['sometimes', 'string'],
            'stripe_customer_id' => ['nullable', 'string'],
            'stripe_subscription_id' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required.',
            'domain.unique' => 'This domain is already taken.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'plan_type.required' => 'Plan type is required.',
            'plan_type.in' => 'Plan type must be one of: free, pro, enterprise.',
            'tokens_monthly_limit.required' => 'Monthly token limit is required.',
            'tokens_monthly_limit.min' => 'Monthly token limit must be at least 0.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: active, inactive, suspended.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'tokens_used_current' => 0,
            'status' => $this->status ?? 'active',
        ]);
    }
}