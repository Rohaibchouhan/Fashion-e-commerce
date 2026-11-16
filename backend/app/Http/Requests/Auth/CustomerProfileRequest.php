<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'nullable|string|max:150',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => "nullable|string|email|max:255|unique:users,email,{$userId}",
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\(\)\s\.]+$/',
            'gender' => 'nullable|string|in:men,women,unspecified',
            'preferred_currency' => 'nullable|string|size:3',
            'marketing_opt_in' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use by another account.',
            'phone.regex' => 'Please provide a valid phone number.',
        ];
    }
}
