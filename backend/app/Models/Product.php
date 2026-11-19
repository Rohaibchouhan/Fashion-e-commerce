<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'subtitle',
        'short_description',
        'description',
        'regular_price',
        'sale_price',
        'cost_price',
        'category_id',
        'brand_id',
        'gender',
        'type',
        'is_active',
        'is_featured',
        'is_new',
        'is_bestseller',
        'is_trending',
        'rating',
        'review_count',
        'fabric',
        'fit',
        'weight',
        'details',
        'care',
        'size_guide',
        'outfit_pairings',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'review_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_trending' => 'boolean',
        'details' => 'array',
        'care' => 'array',
        'size_guide' => 'array',
        'outfit_pairings' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function calculateReviewStats(): array
    {
        return Cache::remember("product.{$this->id}.review_stats", now()->addDay(), function () {
            $stats = $this->reviews()
                ->where('is_approved', true)
                ->selectRaw('
                    COUNT(*) as total_count,
                    COALESCE(AVG(rating), 0) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as count_5,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as count_4,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as count_3,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as count_2,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as count_1
                ')
                ->first();

            $total = (int) ($stats->total_count ?? 0);
            $avg = $total > 0 ? round((float) $stats->avg_rating, 2) : 0.00;

            $counts = [
                '5' => (int) ($stats->count_5 ?? 0),
                '4' => (int) ($stats->count_4 ?? 0),
                '3' => (int) ($stats->count_3 ?? 0),
                '2' => (int) ($stats->count_2 ?? 0),
                '1' => (int) ($stats->count_1 ?? 0),
            ];

            $percentages = [
                '5' => $total > 0 ? round(($counts['5'] / $total) * 100, 1) : 0.0,
                '4' => $total > 0 ? round(($counts['4'] / $total) * 100, 1) : 0.0,
                '3' => $total > 0 ? round(($counts['3'] / $total) * 100, 1) : 0.0,
                '2' => $total > 0 ? round(($counts['2'] / $total) * 100, 1) : 0.0,
                '1' => $total > 0 ? round(($counts['1'] / $total) * 100, 1) : 0.0,
            ];

            $breakdown = [
                ['star' => 5, 'count' => $counts['5'], 'percentage' => $percentages['5']],
                ['star' => 4, 'count' => $counts['4'], 'percentage' => $percentages['4']],
                ['star' => 3, 'count' => $counts['3'], 'percentage' => $percentages['3']],
                ['star' => 2, 'count' => $counts['2'], 'percentage' => $percentages['2']],
                ['star' => 1, 'count' => $counts['1'], 'percentage' => $percentages['1']],
            ];

            return [
                'averageRating' => $avg,
                'totalCount' => $total,
                'counts' => $counts,
                'percentages' => $percentages,
                'breakdown' => $breakdown,
            ];
        });
    }

    public function updateReviewStats(): void
    {
        Cache::forget("product.{$this->id}.review_stats");
        $stats = $this->calculateReviewStats();
        $this->updateQuietly([
            'rating' => $stats['averageRating'],
            'review_count' => $stats['totalCount'],
        ]);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->regular_price);
    }

    public function getIsSaleAttribute(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->regular_price;
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('stock');
    }

    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }
}

