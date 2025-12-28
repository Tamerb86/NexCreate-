<?php

namespace App\Domain\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderMessageRequest extends FormRequest
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
            'message' => ['required_without:attachment', 'nullable', 'string', 'max:10000'],
            'attachment' => ['required_without:message', 'nullable', 'string', 'url', 'max:500'],
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
            'message.required_without' => 'Message or attachment is required.',
            'message.max' => 'Message cannot exceed 10000 characters.',
            'attachment.url' => 'Attachment must be a valid URL.',
            'attachment.max' => 'Attachment URL cannot exceed 500 characters.',
        ];
    }
}
