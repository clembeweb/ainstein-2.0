<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $prompt = $this->route('prompt');

        return $user && (
            $user->is_super_admin ||
            (!$prompt->is_system && $user->tenant_id === $prompt->tenant_id)
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prompt = $this->route('prompt');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'alias' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('prompts')->ignore($prompt->id)
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'template' => ['sometimes', 'string'],
            'variables' => ['sometimes', 'array'],
            'variables.*' => ['string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Prompt name must not exceed 255 characters.',
            'alias.unique' => 'This alias is already taken.',
            'alias.max' => 'Alias must not exceed 100 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'category.max' => 'Category must not exceed 100 characters.',
            'variables.array' => 'Variables must be an array.',
            'variables.*.string' => 'Each variable must be a string.',
        ];
    }
}