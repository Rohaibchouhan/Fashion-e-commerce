<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdminStaff();
    }

    public function rules(): array
    {
        return [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:150',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\(\)\s\.]+$/',
            'password' => $this->has('password_confirmation') && !empty($this->input('password_confirmation'))
                ? 'required|string|min:8|confirmed'
                : 'required|string|min:8',
            'password_confirmation' => 'nullable|string|min:8',
            'role_id' => 'required_without:role|nullable|exists:roles,id',
            'role' => 'nullable|string|exists:roles,slug',
            'status' => 'required|in:active,inactive,pending_activation,suspended',
            // Optional address fields
            'street' => 'nullable|string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid, formatted email address (e.g. name@domain.com).',
            'email.unique' => 'This email address is already registered in the system.',
            'password.required' => 'A secure account password is required.',
            'password.min' => 'The password must contain at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match the entered password.',
            'role_id.required_without' => 'Please select an assigned role for this user.',
            'role_id.exists' => 'The selected role is invalid or does not exist.',
            'role.exists' => 'The specified role slug is invalid.',
            'status.required' => 'Please select an account status.',
            'status.in' => 'Account status must be Active, Inactive, Pending Verification, or Suspended.',
            'phone.regex' => 'Phone number must contain only numbers, spaces, parentheses, or plus/minus signs.',
            'phone.max' => 'Phone number cannot exceed 30 characters.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $firstName = trim($this->input('first_name', ''));
            $lastName = trim($this->input('last_name', ''));
            $name = trim($this->input('name', ''));

            if (empty($name) && empty($firstName)) {
                $validator->errors()->add('first_name', 'Please enter a first name or full name for the user.');
            }
        });
    }
}
