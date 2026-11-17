<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // Normalize shipping address zip variations
        if (isset($input['shippingAddress']) && is_array($input['shippingAddress'])) {
            $zip = $input['shippingAddress']['zipCode']
                ?? $input['shippingAddress']['zip_code']
                ?? $input['shippingAddress']['zip']
                ?? $input['shippingAddress']['postal_code']
                ?? null;
            if ($zip) {
                $input['shippingAddress']['zipCode'] = $zip;
            }

            $firstName = $input['shippingAddress']['firstName']
                ?? $input['shippingAddress']['first_name']
                ?? null;
            if ($firstName) {
                $input['shippingAddress']['firstName'] = $firstName;
            }

            $lastName = $input['shippingAddress']['lastName']
                ?? $input['shippingAddress']['last_name']
                ?? null;
            if ($lastName) {
                $input['shippingAddress']['lastName'] = $lastName;
            }
        }

        // Normalize billing address zip variations
        if (isset($input['billingAddress']) && is_array($input['billingAddress'])) {
            $zip = $input['billingAddress']['zipCode']
                ?? $input['billingAddress']['zip_code']
                ?? $input['billingAddress']['zip']
                ?? $input['billingAddress']['postal_code']
                ?? null;
            if ($zip) {
                $input['billingAddress']['zipCode'] = $zip;
            }

            $firstName = $input['billingAddress']['firstName']
                ?? $input['billingAddress']['first_name']
                ?? null;
            if ($firstName) {
                $input['billingAddress']['firstName'] = $firstName;
            }

            $lastName = $input['billingAddress']['lastName']
                ?? $input['billingAddress']['last_name']
                ?? null;
            if ($lastName) {
                $input['billingAddress']['lastName'] = $lastName;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        $rules = [
            'items' => 'required|array|min:1',
            'items.*.productId' => 'nullable|integer|exists:products,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.variantId' => 'nullable|integer|exists:product_variants,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.color' => 'nullable|string|max:50',
            'items.*.size' => 'nullable|string|max:50',

            // Shipping Address validation
            'shipping_address_id' => 'nullable|integer|exists:addresses,id',
            'shippingAddress' => 'required_without:shipping_address_id|array',
            'shippingAddress.firstName' => 'required_with:shippingAddress|string|max:100',
            'shippingAddress.lastName' => 'required_with:shippingAddress|string|max:100',
            'shippingAddress.street' => 'required_with:shippingAddress|string|max:255',
            'shippingAddress.apartment' => 'nullable|string|max:100',
            'shippingAddress.city' => 'required_with:shippingAddress|string|max:100',
            'shippingAddress.state' => 'required_with:shippingAddress|string|max:100',
            'shippingAddress.zipCode' => 'required_with:shippingAddress|string|max:20',
            'shippingAddress.country' => 'nullable|string|max:100',
            'shippingAddress.phone' => 'nullable|string|max:30',

            // Billing Address
            'billing_address_id' => 'nullable|integer|exists:addresses,id',
            'billing_same_as_shipping' => 'nullable|boolean',
            'billingAddress' => 'nullable|array',
            'billingAddress.firstName' => 'required_with:billingAddress|string|max:100',
            'billingAddress.lastName' => 'required_with:billingAddress|string|max:100',
            'billingAddress.street' => 'required_with:billingAddress|string|max:255',
            'billingAddress.apartment' => 'nullable|string|max:100',
            'billingAddress.city' => 'required_with:billingAddress|string|max:100',
            'billingAddress.state' => 'required_with:billingAddress|string|max:100',
            'billingAddress.zipCode' => 'required_with:billingAddress|string|max:20',
            'billingAddress.country' => 'nullable|string|max:100',
            'billingAddress.phone' => 'nullable|string|max:30',

            // Shipping & Coupon
            'shippingMethodId' => 'nullable|string|max:50',
            'shipping_method_id' => 'nullable|string|max:50',
            'couponCode' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string|max:50',

            // Payment & Metadata
            'payment' => 'nullable|array',
            'paymentMethod' => 'nullable',
            'payment_method' => 'nullable',
            'payment_type' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];

        // Customer account requirement when user is unauthenticated
        if (!$this->user('sanctum')) {
            $rules['customer'] = 'required_without:autoRegister|array';
            $rules['customer.email'] = 'required_with:customer|email|max:255';
            $rules['customer.name'] = 'required_with:customer|string|max:100';
            $rules['customer.phone'] = 'nullable|string|max:30';

            $rules['autoRegister'] = 'required_without:customer|array';
            $rules['autoRegister.email'] = 'required_with:autoRegister|email|max:255';
            $rules['autoRegister.name'] = 'required_with:autoRegister|string|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required to checkout.',
            'items.min' => 'At least one item is required to checkout.',
            'shipping_address_id.exists' => 'The selected shipping address could not be found.',
            'shippingAddress.required_without' => 'Please provide a valid shipping address or select a saved address.',
            'shippingAddress.firstName.required_with' => 'First name is required on the shipping address.',
            'shippingAddress.lastName.required_with' => 'Last name is required on the shipping address.',
            'shippingAddress.street.required_with' => 'Street address is required on the shipping address.',
            'shippingAddress.city.required_with' => 'City is required on the shipping address.',
            'shippingAddress.state.required_with' => 'State is required on the shipping address.',
            'shippingAddress.zipCode.required_with' => 'Postal or ZIP code is required on the shipping address.',
            'customer.required_without' => 'Customer account details or authentication are required for checkout.',
            'customer.email.required_with' => 'A valid customer email is required to create your account.',
            'customer.name.required_with' => 'Customer name is required.',
        ];
    }
}
