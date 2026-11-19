<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $colors = [];
        $sizes = [];

        foreach ($this->variants as $v) {
            if ($v->color && !isset($colors[$v->color])) {
                $colors[$v->color] = [
                    'name' => $v->color,
                    'hex' => $v->color_hex ?? '#000000',
                    'image' => $v->image ? (str_starts_with($v->image, 'http') ? $v->image : url($v->image)) : null,
                ];
            }

            if ($v->size && !isset($sizes[$v->size])) {
                $sizes[$v->size] = [
                    'name' => $v->size,
                    'inStock' => $v->available_stock > 0,
                    'stockCount' => (int) $v->available_stock,
                ];
            }
        }

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'name' => $this->name,
            'subtitle' => $this->subtitle ?? '',
            'description' => $this->description ?? '',
            'category' => $this->category?->name ?? 'General',
            'categorySlug' => $this->category?->slug ?? 'all',
            'gender' => $this->gender,
            'price' => (float) ($this->sale_price ?? $this->regular_price),
            'compareAtPrice' => $this->sale_price ? (float) $this->regular_price : null,
            'isSale' => $this->is_sale,
            'isNew' => $this->is_new,
            'isBestSeller' => $this->is_bestseller,
            'isTrending' => $this->is_trending,
            'rating' => (float) $this->rating,
            'reviewCount' => (int) $this->review_count,
            'ratingStats' => $this->calculateReviewStats(),
            'images' => $this->images->map(function ($img) {
                if (str_starts_with($img->url, 'http://') || str_starts_with($img->url, 'https://')) {
                    return $img->url;
                }
                return url($img->url);
            })->toArray(),
            'colors' => array_values($colors),
            'sizes' => array_values($sizes),
            'variants' => $this->variants->map(fn ($v) => [
                'id' => (string) $v->id,
                'sku' => $v->sku,
                'color' => $v->color ?? '',
                'size' => $v->size ?? '',
                'price' => (float) $v->price,
                'compareAtPrice' => $v->compare_at_price ? (float) $v->compare_at_price : null,
                'stock' => (int) $v->available_stock,
                'image' => $v->image ? (str_starts_with($v->image, 'http') ? $v->image : url($v->image)) : null,
            ])->toArray(),
            'details' => $this->details ?? [],
            'care' => $this->care ?? [],
            'fabric' => $this->fabric ?? '',
            'fit' => $this->fit ?? '',
            'inStock' => $this->in_stock,
            'outfitPairings' => $this->outfit_pairings ?? [],
            'reviews' => ($this->relationLoaded('reviews')
                ? $this->reviews->where('is_approved', true)->take(10)
                : $this->reviews()->where('is_approved', true)->latest()->take(10)->with('user')->get()
            )->map(fn ($r) => [
                'id' => (string) $r->id,
                'author' => $r->user?->name ?? 'Verified Buyer',
                'rating' => (int) $r->rating,
                'date' => $r->created_at->format('M d, Y'),
                'title' => $r->title,
                'comment' => $r->comment,
                'verified' => (bool) $r->is_verified_purchase,
            ])->values()->toArray(),
        ];
    }
}

