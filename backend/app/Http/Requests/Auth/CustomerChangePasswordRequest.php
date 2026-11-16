<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => $this->has('new_password_confirmation')
                ? 'required|string|min:8|different:current_password|confirmed'
                : 'required|string|min:8|different:current_password',
            'new_password_confirmation' => 'nullable|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Your current account password is required.',
            'new_password.required' => 'Please provide a new password.',
            'new_password.min' => 'The new password must be at least 8 characters long.',
            'new_password.different' => 'The new password must be different from your current password.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ];
    }
}
