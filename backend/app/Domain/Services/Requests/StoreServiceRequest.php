<?php

namespace App\Domain\Services\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only creators can create services
        return auth()->check() && auth()->user()->isCreator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'price' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'delivery_time' => ['required', 'integer', 'min:1', 'max:365'],
            'revisions' => ['required', 'integer', 'min:0', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
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
            'title.required' => 'Service title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Service description is required.',
            'description.min' => 'Description must be at least 50 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be at least 1 NOK.',
            'price.max' => 'Price cannot exceed 999,999.99 NOK.',
            'delivery_time.required' => 'Delivery time is required.',
            'delivery_time.min' => 'Delivery time must be at least 1 day.',
            'delivery_time.max' => 'Delivery time cannot exceed 365 days.',
            'revisions.required' => 'Number of revisions is required.',
            'revisions.min' => 'Revisions cannot be negative.',
            'revisions.max' => 'Revisions cannot exceed 100.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.url' => 'Each image must be a valid URL.',
        ];
    }
}
