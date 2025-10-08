<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'role' => ['sometimes', 'string', 'in:user,admin'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'tenant_id.required' => 'Tenant is required.',
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'role.in' => 'Role must be either user or admin.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default role to 'user' if not provided
        if (!$this->has('role')) {
            $this->merge(['role' => 'user']);
        }

        // Set default values
        $this->merge([
            'is_active' => true,
            'email_verified' => false,
            'is_super_admin' => false,
        ]);
    }
}