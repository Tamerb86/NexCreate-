<?php

namespace App\Domain\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderDeliveryRequest extends FormRequest
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
            'file_url' => ['required', 'string', 'url', 'max:500'],
            'message' => ['nullable', 'string', 'max:5000'],
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
            'file_url.required' => 'File URL is required for delivery.',
            'file_url.url' => 'File URL must be a valid URL.',
            'file_url.max' => 'File URL cannot exceed 500 characters.',
            'message.max' => 'Message cannot exceed 5000 characters.',
        ];
    }
}
