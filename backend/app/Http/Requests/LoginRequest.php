<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'remember' => 'sometimes|boolean',
            'captcha_token' => 'sometimes|string',
            'captcha_answer' => 'sometimes|string',
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'L\'identifiant est requis.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
