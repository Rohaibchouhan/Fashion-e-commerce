<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => $this->has('password_confirmation') && !empty($this->input('password_confirmation'))
                ? 'required|string|min:8|confirmed'
                : 'required|string|min:8',
            'password_confirmation' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\(\)\s\.]+$/',
            'gender' => 'nullable|string|in:men,women,unspecified',
            'preferred_currency' => 'nullable|string|size:3',
            'marketing_opt_in' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'name.max' => 'Your name cannot exceed 150 characters.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please enter a valid email address (e.g. name@example.com).',
            'email.unique' => 'This email address is already registered. Please sign in or use another email.',
            'password.required' => 'Please create a secure password.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
