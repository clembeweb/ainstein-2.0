<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $page = $this->route('page');

        return $user && (
            $user->is_super_admin ||
            $user->tenant_id === $page->tenant_id
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
            'url_path' => ['sometimes', 'string', 'max:500'],
            'keyword' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'language' => ['sometimes', 'string', 'size:2'],
            'cms_type' => ['sometimes', 'nullable', 'string', 'in:wordpress,drupal,joomla,custom'],
            'cms_page_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:draft,active,inactive,pending'],
            'priority' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'url_path.max' => 'URL path must not exceed 500 characters.',
            'keyword.max' => 'Keyword must not exceed 255 characters.',
            'category.max' => 'Category must not exceed 100 characters.',
            'language.size' => 'Language must be a 2-character code (e.g., "en", "it").',
            'cms_type.in' => 'CMS type must be one of: wordpress, drupal, joomla, custom.',
            'status.in' => 'Status must be one of: draft, active, inactive, pending.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority must not exceed 10.',
        ];
    }
}