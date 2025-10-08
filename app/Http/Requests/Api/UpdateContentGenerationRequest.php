<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentGenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $generation = $this->route('contentGeneration') ?? $this->route('generation');

        return $user && (
            $user->is_super_admin ||
            $user->tenant_id === $generation->tenant_id
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt_type' => ['sometimes', 'string', 'max:100'],
            'generated_content' => ['sometimes', 'nullable', 'string'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tokens_used' => ['sometimes', 'integer', 'min:0'],
            'ai_model' => ['sometimes', 'nullable', 'string', 'in:gpt-3.5-turbo,gpt-4,gpt-4-turbo,claude-3-sonnet,claude-3-opus'],
            'status' => ['sometimes', 'string', 'in:pending,processing,completed,failed,published'],
            'error' => ['sometimes', 'nullable', 'string'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'prompt_type.max' => 'Prompt type must not exceed 100 characters.',
            'meta_title.max' => 'Meta title must not exceed 255 characters.',
            'meta_description.max' => 'Meta description must not exceed 500 characters.',
            'tokens_used.min' => 'Tokens used must be at least 0.',
            'ai_model.in' => 'AI model must be one of: gpt-3.5-turbo, gpt-4, gpt-4-turbo, claude-3-sonnet, claude-3-opus.',
            'status.in' => 'Status must be one of: pending, processing, completed, failed, published.',
            'published_at.date' => 'Published at must be a valid date.',
        ];
    }
}