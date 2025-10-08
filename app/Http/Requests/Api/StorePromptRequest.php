<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePromptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tenant_id;
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
            'alias' => ['nullable', 'string', 'max:100', 'unique:prompts,alias'],
            'description' => ['nullable', 'string', 'max:1000'],
            'template' => ['required', 'string'],
            'variables' => ['sometimes', 'array'],
            'variables.*' => ['string'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Prompt name is required.',
            'name.max' => 'Prompt name must not exceed 255 characters.',
            'alias.unique' => 'This alias is already taken.',
            'alias.max' => 'Alias must not exceed 100 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'template.required' => 'Prompt template is required.',
            'category.max' => 'Category must not exceed 100 characters.',
            'variables.array' => 'Variables must be an array.',
            'variables.*.string' => 'Each variable must be a string.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_system' => false, // User-created prompts are never system prompts
        ]);
    }
}