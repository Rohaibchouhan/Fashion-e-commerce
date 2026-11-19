<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasPermission('admin_users.manage');
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');
        $userId = is_object($targetUser) ? $targetUser->id : $targetUser;

        return [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:150',
            'email' => "required|string|email|max:255|unique:users,email,{$userId}",
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\(\)\s\.]+$/',
            'password' => $this->has('password_confirmation') && !empty($this->input('password'))
                ? 'nullable|string|min:8|confirmed'
                : 'nullable|string|min:8',
            'password_confirmation' => 'nullable|string|min:8',
            'role_id' => 'sometimes|nullable|exists:roles,id',
            'role' => 'sometimes|nullable|string|exists:roles,slug',
            'status' => 'sometimes|in:active,inactive,pending_activation,suspended',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid, formatted email address (e.g. name@domain.com).',
            'email.unique' => 'This email address is already assigned to another user account.',
            'password.min' => 'The new password must contain at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match the new password.',
            'role_id.exists' => 'The selected role is invalid or does not exist.',
            'role.exists' => 'The specified role slug is invalid.',
            'status.in' => 'Account status must be Active, Inactive, Pending Verification, or Suspended.',
            'phone.regex' => 'Phone number must contain only numbers, spaces, parentheses, or plus/minus signs.',
            'phone.max' => 'Phone number cannot exceed 30 characters.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $firstName = trim($this->input('first_name', ''));
            $name = trim($this->input('name', ''));

            if ($this->has('name') && empty($name) && empty($firstName)) {
                $validator->errors()->add('name', 'The user name cannot be empty.');
            }
        });
    }
}
