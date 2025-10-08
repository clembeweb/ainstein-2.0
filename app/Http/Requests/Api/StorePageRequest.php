<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
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
            'url_path' => ['required', 'string', 'max:500'],
            'keyword' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'language' => ['required', 'string', 'size:2'],
            'cms_type' => ['nullable', 'string', 'in:wordpress,drupal,joomla,custom'],
            'cms_page_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,active,inactive,pending'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'url_path.required' => 'URL path is required.',
            'url_path.max' => 'URL path must not exceed 500 characters.',
            'keyword.required' => 'Keyword is required.',
            'keyword.max' => 'Keyword must not exceed 255 characters.',
            'category.max' => 'Category must not exceed 100 characters.',
            'language.required' => 'Language is required.',
            'language.size' => 'Language must be a 2-character code (e.g., "en", "it").',
            'cms_type.in' => 'CMS type must be one of: wordpress, drupal, joomla, custom.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: draft, active, inactive, pending.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority must not exceed 10.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'status' => $this->status ?? 'draft',
            'priority' => $this->priority ?? 5,
            'language' => $this->language ?? 'en',
        ]);
    }
}