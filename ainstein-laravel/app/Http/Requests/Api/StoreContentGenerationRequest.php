<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentGenerationRequest extends FormRequest
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
            'page_id' => ['required', 'exists:pages,id'],
            'prompt_id' => ['required', 'exists:prompts,id'],
            'prompt_type' => ['required', 'string', 'max:100'],
            'prompt_template' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'additional_instructions' => ['nullable', 'string'],
            'ai_model' => ['nullable', 'string', 'in:gpt-3.5-turbo,gpt-4,gpt-4-turbo,claude-3-sonnet,claude-3-opus'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'page_id.required' => 'Page is required.',
            'page_id.exists' => 'The selected page does not exist.',
            'prompt_id.required' => 'Prompt is required.',
            'prompt_id.exists' => 'The selected prompt does not exist.',
            'prompt_type.required' => 'Prompt type is required.',
            'prompt_type.max' => 'Prompt type must not exceed 100 characters.',
            'prompt_template.required' => 'Prompt template is required.',
            'ai_model.in' => 'AI model must be one of: gpt-3.5-turbo, gpt-4, gpt-4-turbo, claude-3-sonnet, claude-3-opus.',
            'meta_title.max' => 'Meta title must not exceed 255 characters.',
            'meta_description.max' => 'Meta description must not exceed 500 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'ai_model' => $this->ai_model ?? 'gpt-3.5-turbo',
            'status' => 'pending',
            'tokens_used' => 0,
        ]);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verify that the page belongs to the user's tenant
            if ($this->page_id) {
                $page = \App\Models\Page::find($this->page_id);
                if ($page && $page->tenant_id !== $this->user()->tenant_id) {
                    $validator->errors()->add('page_id', 'You can only create content generations for pages in your tenant.');
                }
            }
        });
    }
}