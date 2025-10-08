<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $tenant = $this->route('tenant');

        return $user && (
            $user->is_super_admin ||
            ($user->tenant_id === $tenant->id && $user->role === 'admin')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants')->ignore($tenant->id)
            ],
            'subdomain' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('tenants')->ignore($tenant->id)
            ],
            'plan_type' => ['sometimes', 'string', 'in:free,pro,enterprise'],
            'tokens_monthly_limit' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'theme_config' => ['sometimes', 'array'],
            'brand_config' => ['sometimes', 'array'],
            'features' => ['sometimes', 'string'],
            'stripe_customer_id' => ['sometimes', 'nullable', 'string'],
            'stripe_subscription_id' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'domain.unique' => 'This domain is already taken.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'plan_type.in' => 'Plan type must be one of: free, pro, enterprise.',
            'tokens_monthly_limit.min' => 'Monthly token limit must be at least 0.',
            'status.in' => 'Status must be one of: active, inactive, suspended.',
        ];
    }
}