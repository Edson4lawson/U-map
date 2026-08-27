<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:30|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])/',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom d\'utilisateur est requis.',
            'name.min' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom d\'utilisateur ne peut pas dépasser 30 caractères.',
            'name.regex' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores.',
            'email.required' => 'L\'adresse e-mail est requise.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, un chiffre et un caractère spécial.',
        ];
    }
}
