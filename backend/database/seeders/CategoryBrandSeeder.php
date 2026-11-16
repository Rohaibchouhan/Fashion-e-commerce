<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryBrandSeeder extends Seeder
{
    public function run(): void
    {
        // Brands
        $brands = [
            ['name' => 'WearDay Studio', 'slug' => 'wearday-studio', 'description' => 'In-house signature modern tailoring and everyday elevated wardrobe staples.'],
            ['name' => 'Urban Thread', 'slug' => 'urban-thread', 'description' => 'Contemporary streetwear and relaxed aesthetic tailored for modern life.'],
            ['name' => 'Minimalist Co', 'slug' => 'minimalist-co', 'description' => 'Clean architectural silhouettes made from premium organic textiles.'],
            ['name' => 'Artisan Atelier', 'slug' => 'artisan-atelier', 'description' => 'Heritage hand-finished luxury outerwear and structured evening tailoring.'],
            ['name' => 'EcoKnit', 'slug' => 'ecoknit', 'description' => 'Sustainable Merino wool and recycled cashmere essentials.'],
        ];

        foreach ($brands as $b) {
            Brand::firstOrCreate(['slug' => $b['slug']], $b);
        }

        // Top level categories
        $men = Category::firstOrCreate(['slug' => 'men'], [
            'name' => "Men's Clothing",
            'gender' => 'men',
            'description' => 'Refined menswear engineered for clean silhouettes and effortless comfort.',
            'image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $women = Category::firstOrCreate(['slug' => 'women'], [
            'name' => "Women's Clothing",
            'gender' => 'women',
            'description' => 'Elevated women’s wardrobe classics blending modern sophistication and timeless design.',
            'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
            'sort_order' => 2,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $accessories = Category::firstOrCreate(['slug' => 'accessories'], [
            'name' => 'Accessories',
            'gender' => 'all',
            'description' => 'Curated premium leather bags, wool scarves, hats, and minimalist accents.',
            'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80',
            'sort_order' => 3,
            'is_active' => true,
            'is_featured' => true,
        ]);

        // Men Subcategories
        $menSubs = [
            ['name' => 'Shirts', 'slug' => 'men-shirts', 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'T-Shirts & Polos', 'slug' => 'men-t-shirts', 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Jeans & Trousers', 'slug' => 'men-jeans', 'image' => 'https://images.unsplash.com/photo-1542272604-780c96856592?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Jackets & Coats', 'slug' => 'men-jackets', 'image' => 'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Hoodies & Sweats', 'slug' => 'men-hoodies', 'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&auto=format&fit=crop&q=80'],
        ];

        foreach ($menSubs as $sub) {
            Category::firstOrCreate(['slug' => $sub['slug']], array_merge($sub, [
                'parent_id' => $men->id,
                'gender' => 'men',
                'is_active' => true,
            ]));
        }

        // Women Subcategories
        $womenSubs = [
            ['name' => 'Dresses', 'slug' => 'women-dresses', 'image' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Tops & Blouses', 'slug' => 'women-tops', 'image' => 'https://images.unsplash.com/photo-1534126511673-b6899657816a?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Jeans & Pants', 'slug' => 'women-jeans', 'image' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Blazers & Outerwear', 'slug' => 'women-jackets', 'image' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=600&auto=format&fit=crop&q=80'],
            ['name' => 'Knitwear & Sweaters', 'slug' => 'women-knitwear', 'image' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600&auto=format&fit=crop&q=80'],
        ];

        foreach ($womenSubs as $sub) {
            Category::firstOrCreate(['slug' => $sub['slug']], array_merge($sub, [
                'parent_id' => $women->id,
                'gender' => 'women',
                'is_active' => true,
            ]));
        }
    }
}

