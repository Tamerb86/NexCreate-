<?php

namespace App\Domain\Services\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:50', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:1', 'max:999999.99'],
            'delivery_time' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'revisions' => ['sometimes', 'required', 'integer', 'min:0', 'max:100'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['string', 'url', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.min' => 'Description must be at least 50 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'price.min' => 'Price must be at least 1 NOK.',
            'price.max' => 'Price cannot exceed 999,999.99 NOK.',
            'delivery_time.min' => 'Delivery time must be at least 1 day.',
            'delivery_time.max' => 'Delivery time cannot exceed 365 days.',
            'revisions.min' => 'Revisions cannot be negative.',
            'revisions.max' => 'Revisions cannot exceed 100.',
            'category_id.exists' => 'Selected category does not exist.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.url' => 'Each image must be a valid URL.',
        ];
    }
}
