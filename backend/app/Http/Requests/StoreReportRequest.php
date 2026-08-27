<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReportRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                'in:inappropriate_content,spam,harassment,fake_location,other',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'place_id' => [
                'sometimes',
                'exists:places,id',
            ],
            'user_id' => [
                'sometimes',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Cannot report self
                    if ($value == $this->user()->id) {
                        $fail('You cannot report yourself.');
                    }
                },
            ],
            'evidence_url' => [
                'nullable',
                'url',
                'max:500',
                'regex:/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp|pdf)$/i',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Report type is required.',
            'type.in' => 'Invalid report type.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'place_id.exists' => 'Place does not exist.',
            'user_id.exists' => 'User does not exist.',
            'evidence_url.url' => 'Evidence must be a valid URL.',
            'evidence_url.regex' => 'Evidence must be a valid image or PDF URL.',
        ];
    }

    /**
     * Sanitize the input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->sanitizeString($this->input('description', '')),
        ]);
    }

    /**
     * Sanitize string input.
     */
    protected function sanitizeString(string $input): string
    {
        $input = strip_tags($input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return trim($input);
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
