<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Enums\UserRole;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $target = $this->route('user');

        // Users can update their own profile
        if ($user->id === $target->id) {
            return true;
        }

        // Admins can update other users
        if ($user->can('manage-users')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user();
        $target = $this->route('user');
        $isOwnProfile = $user->id === $target->id;

        return [
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\p{N}\p{P}\p{S}\s-]+$/u',
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'unique:users,email,' . $target->id,
            ],
            'role' => [
                'sometimes',
                'in:' . implode(',', UserRole::all()),
                function ($attribute, $value, $fail) use ($user, $target, $isOwnProfile) {
                    // Cannot change own role
                    if ($isOwnProfile) {
                        $fail('You cannot change your own role.');
                        return;
                    }

                    // Only admins can change roles
                    if (!$user->can('manage-users')) {
                        $fail('You do not have permission to change roles.');
                        return;
                    }

                    // Check role hierarchy
                    $userRole = UserRole::from($user->role);
                    $newRole = UserRole::from($value);
                    $targetRole = UserRole::from($target->role);

                    if ($userRole->level() <= $newRole->level()) {
                        $fail('You can only assign roles lower than your own.');
                        return;
                    }

                    if (!$userRole->canManage($targetRole)) {
                        $fail('You cannot manage users with this role.');
                        return;
                    }
                },
            ],
            'study_status' => [
                'sometimes',
                'string',
                'in:student,faculty,staff,visitor,alumni',
            ],
            'study_location' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'is_restricted' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) use ($user, $target, $isOwnProfile) {
                    // Cannot restrict/unrestrict self
                    if ($isOwnProfile) {
                        $fail('You cannot restrict yourself.');
                        return;
                    }

                    // Only moderators can restrict users
                    if (!$user->can('manage-users')) {
                        $fail('You do not have permission to restrict users.');
                        return;
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.regex' => 'Name contains invalid characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'email.unique' => 'Email already exists.',
            'role.in' => 'Invalid role selected.',
            'study_status.in' => 'Invalid study status.',
        ];
    }

    /**
     * Sanitize the input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeString($this->input('name')),
            'study_location' => $this->sanitizeString($this->input('study_location')),
        ]);
    }

    /**
     * Sanitize string input.
     */
    protected function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

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
