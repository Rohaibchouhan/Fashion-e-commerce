<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'street' => 'required|string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\(\)\s\.]+$/',
            'is_default' => 'nullable|boolean',
            'is_default_shipping' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required for delivery.',
            'last_name.required' => 'Last name is required for delivery.',
            'street.required' => 'Street address is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State or province is required.',
            'zip_code.required' => 'Postal or ZIP code is required.',
            'country.required' => 'Country is required.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
