<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ! $this->user()->is_restricted;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                'max:100',
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du lieu est requis.',
            'type.required' => 'Le type du lieu est requis.',
            'latitude.required' => 'La latitude est requise.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.required' => 'La longitude est requise.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeString($this->input('name', '')),
            'type' => $this->sanitizeString($this->input('type', '')),
            'category' => $this->sanitizeString($this->input('category', '')),
            'description' => $this->sanitizeString($this->input('description', '')),
        ]);
    }

    protected function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        $input = strip_tags($input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        return trim($input);
    }

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
