<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && !$this->user()->is_restricted;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Prevent sending to self
                    if ($value == $this->user()->id) {
                        $fail('Vous ne pouvez pas vous envoyer de message à vous-même.');
                    }

                    $receiver = \App\Models\User::find($value);
                    if ($receiver && $receiver->is_restricted && ! $this->user()->can('manage-users')) {
                        $fail('Impossible d\'envoyer un message à cet utilisateur.');
                    }
                },
            ],
            'content' => [
                'required',
                'string',
                'min:1',
                'max:2000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'The recipient is required.',
            'receiver_id.exists' => 'The recipient does not exist.',
            'content.required' => 'Message content is required.',
            'content.min' => 'Message cannot be empty.',
            'content.max' => 'Message cannot exceed 1000 characters.',
            'content.regex' => 'Message contains invalid characters.',
        ];
    }

    /**
     * Sanitize the input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'content' => $this->sanitizeContent($this->input('content', '')),
        ]);
    }

    /**
     * Sanitize message content.
     */
    protected function sanitizeContent(string $content): string
    {
        // Remove HTML tags
        $content = strip_tags($content);
        
        // Remove potentially dangerous control characters (except \n and \r)
        $content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Standardize newlines
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        // Normalize horizontal whitespace (spaces & tabs)
        $content = preg_replace('/[ \t]+/', ' ', $content);
        
        // Limit consecutive newlines to maximum 2
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422)
        );
    }
}
