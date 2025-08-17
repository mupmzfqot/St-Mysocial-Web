<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:200',
            'sort_by' => 'sometimes|string|in:created_at,file_name,size',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'mime_type' => 'sometimes|string|regex:/^video\/.+/',
            'extension' => 'sometimes|string|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
            'page.min' => 'Page number must be at least 1',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 200',
            'sort_by.in' => 'Sort by must be one of: created_at, file_name, size',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'mime_type.regex' => 'Mime type must be a valid video format',
            'extension.max' => 'Extension cannot exceed 10 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'per page',
            'page' => 'page number',
            'limit' => 'limit',
            'sort_by' => 'sort by',
            'sort_order' => 'sort order',
            'mime_type' => 'mime type',
            'extension' => 'extension',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        $this->merge([
            'per_page' => $this->get('per_page', 15),
            'page' => $this->get('page', 1),
            'sort_by' => $this->get('sort_by', 'created_at'),
            'sort_order' => $this->get('sort_order', 'desc'),
        ]);
    }
}
