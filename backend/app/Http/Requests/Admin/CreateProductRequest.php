<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdminStaff();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'sku' => 'required|string|max:64|unique:products,sku',
            'subtitle' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'required|string',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:regular_price',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'gender' => 'required|in:men,women,unisex',
            'fabric' => 'nullable|string|max:255',
            'fit' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'is_new' => 'nullable|boolean',
            'is_bestseller' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'main_image_url' => 'nullable|url',
            'variants' => 'nullable|array',
            'variants.*.size' => 'nullable|string|max:32',
            'variants.*.color' => 'nullable|string|max:64',
            'variants.*.color_hex' => 'nullable|string|max:16',
            'variants.*.sku' => 'nullable|string|max:64|unique:product_variants,sku',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.compare_at_price' => 'nullable|numeric|min:0',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'name.max' => 'The product name cannot exceed 255 characters.',
            'slug.unique' => 'This URL slug is already taken by another product.',
            'sku.required' => 'A unique product SKU identifier is required.',
            'sku.unique' => 'This base SKU is already assigned to another garment in the catalog.',
            'description.required' => 'A detailed product description is required.',
            'regular_price.required' => 'A regular selling price is required.',
            'regular_price.numeric' => 'Regular price must be a valid number.',
            'regular_price.min' => 'Regular price cannot be negative.',
            'sale_price.numeric' => 'Sale price must be a valid number.',
            'sale_price.min' => 'Sale price cannot be negative.',
            'sale_price.lte' => 'Sale price cannot exceed the regular retail price.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'cost_price.min' => 'Cost price cannot be negative.',
            'category_id.required' => 'Please select a catalog category for this product.',
            'category_id.exists' => 'The selected catalog category does not exist.',
            'brand_id.exists' => 'The selected fashion brand does not exist.',
            'gender.required' => 'Please select the target department (Men, Women, or Unisex).',
            'gender.in' => 'Target department must be men, women, or unisex.',
            'main_image.image' => 'The primary cover image must be an image file.',
            'main_image.mimes' => 'Primary cover image must be a JPEG, PNG, JPG, WEBP, or GIF file.',
            'main_image.max' => 'Primary cover image cannot exceed 5MB.',
            'gallery_images.*.image' => 'Each gallery upload must be an image file.',
            'gallery_images.*.mimes' => 'Gallery images must be JPEG, PNG, JPG, WEBP, or GIF files.',
            'gallery_images.*.max' => 'Gallery image files cannot exceed 5MB each.',
            'variants.*.sku.unique' => 'One or more variant SKUs conflict with an existing garment in the catalog.',
            'variants.*.stock.integer' => 'Variant inventory stock must be a whole number.',
            'variants.*.stock.min' => 'Variant inventory stock cannot be negative.',
            'variants.*.price.min' => 'Variant price cannot be negative.',
        ];
    }
}
