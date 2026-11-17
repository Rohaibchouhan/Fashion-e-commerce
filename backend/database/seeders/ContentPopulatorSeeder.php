<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ContentPopulatorSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Populating Brands...');
        $brands = $this->seedBrands();

        $this->command?->info('Populating Categories...');
        $categories = $this->seedCategories();

        $this->command?->info('Populating Product Attributes & Tags...');
        $tags = $this->seedAttributesAndTags();

        $this->command?->info('Populating Products, Variants & Inventories...');
        $this->seedProducts($brands, $categories, $tags);

        $this->command?->info('Populating Banners...');
        $this->seedBanners();

        $this->command?->info('Populating Coupons...');
        $this->seedCoupons();

        $this->command?->info('Populating Blog Categories, Tags & Posts...');
        $this->seedBlog();

        $this->command?->info('Populating Customer Reviews...');
        $this->seedReviews();

        $this->command?->info('Content population complete!');
    }

    private function seedBrands(): array
    {
        $brandsData = [
            [
                'name' => 'WearDay Studio',
                'slug' => 'wearday-studio',
                'description' => 'In-house signature modern tailoring and everyday elevated wardrobe staples designed in New York.',
                'website' => 'https://wearday.com',
                'logo' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Urban Thread',
                'slug' => 'urban-thread',
                'description' => 'Contemporary streetwear and relaxed aesthetic tailored for fluid city life and daily transit.',
                'website' => 'https://urbanthread.co',
                'logo' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Minimalist Co',
                'slug' => 'minimalist-co',
                'description' => 'Clean architectural silhouettes made from premium organic textiles and natural earth dyes.',
                'website' => 'https://minimalistco.design',
                'logo' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Artisan Atelier',
                'slug' => 'artisan-atelier',
                'description' => 'Heritage hand-finished luxury outerwear and structured evening tailoring from Milan.',
                'website' => 'https://artisanatelier.it',
                'logo' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'EcoKnit',
                'slug' => 'ecoknit',
                'description' => 'Sustainable Merino wool and recycled cashmere essentials certified under the Responsible Wool Standard.',
                'website' => 'https://ecoknitwear.org',
                'logo' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Nordic Woolens',
                'slug' => 'nordic-woolens',
                'description' => 'Sub-zero thermal comfort and Scandinavian heavy-gauge knitwear built to endure the elements.',
                'website' => 'https://nordicwoolens.se',
                'logo' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Monochrome Lab',
                'slug' => 'monochrome-lab',
                'description' => 'Technical fabrics, utilitarian cuts, and minimalist grayscale colorways for modern explorers.',
                'website' => 'https://monochromelab.tokyo',
                'logo' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Atelier Riviera',
                'slug' => 'atelier-riviera',
                'description' => 'Sun-drenched Mediterranean tailoring, breathable French flax linens, and effortless warm-weather elegance.',
                'website' => 'https://atelier-riviera.fr',
                'logo' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=200&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['slug']] = Brand::updateOrCreate(['slug' => $b['slug']], $b);
        }
        return $brands;
    }

    private function seedCategories(): array
    {
        // Parent Categories
        $parents = [
            'men' => [
                'name' => "Men's Clothing",
                'slug' => 'men',
                'gender' => 'men',
                'description' => 'Refined menswear engineered for clean silhouettes and effortless comfort.',
                'image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'seo_title' => "Men's Clothing & Essentials — WearDay",
                'seo_description' => 'Shop modern tailoring, organic tees, selvedge denim, and premium outerwear for men.',
            ],
            'women' => [
                'name' => "Women's Clothing",
                'slug' => 'women',
                'gender' => 'women',
                'description' => 'Elevated women’s wardrobe classics blending modern sophistication and timeless design.',
                'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'seo_title' => "Women's Fashion & Capsule Wardrobe — WearDay",
                'seo_description' => 'Explore refined women’s dresses, tailoring, knitwear, and effortless separates.',
            ],
            'accessories' => [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'gender' => 'all',
                'description' => 'Curated premium leather bags, wool scarves, hats, and minimalist accents.',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'seo_title' => 'Minimalist Leather & Lifestyle Accessories — WearDay',
                'seo_description' => 'Handcrafted full-grain leather bags, cashmere scarves, and luxury essentials.',
            ],
            'shoes' => [
                'name' => 'Footwear',
                'slug' => 'shoes',
                'gender' => 'all',
                'description' => 'Artisanal leather sneakers, suede Chelsea boots, and timeless minimalist shoes.',
                'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'seo_title' => 'Handcrafted Footwear & Sneakers — WearDay',
                'seo_description' => 'Clean leather low-top sneakers and Goodyear-welted boots engineered for daily wear.',
            ],
        ];

        $categories = [];
        foreach ($parents as $key => $p) {
            $categories[$key] = Category::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // Subcategories
        $subs = [
            // Men
            ['name' => 'Shirts', 'slug' => 'men-shirts', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&auto=format&fit=crop&q=80', 'sort_order' => 1],
            ['name' => 'T-Shirts & Polos', 'slug' => 'men-t-shirts', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80', 'sort_order' => 2],
            ['name' => 'Jeans & Trousers', 'slug' => 'men-jeans', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1542272604-780c96856592?w=600&auto=format&fit=crop&q=80', 'sort_order' => 3],
            ['name' => 'Jackets & Coats', 'slug' => 'men-jackets', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=600&auto=format&fit=crop&q=80', 'sort_order' => 4],
            ['name' => 'Hoodies & Sweats', 'slug' => 'men-hoodies', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&auto=format&fit=crop&q=80', 'sort_order' => 5],
            ['name' => 'Knitwear & Sweaters', 'slug' => 'men-knitwear', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&auto=format&fit=crop&q=80', 'sort_order' => 6],
            ['name' => 'Shorts & Casual', 'slug' => 'men-shorts', 'parent' => 'men', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=600&auto=format&fit=crop&q=80', 'sort_order' => 7],

            // Women
            ['name' => 'Dresses', 'slug' => 'women-dresses', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&auto=format&fit=crop&q=80', 'sort_order' => 1],
            ['name' => 'Tops & Blouses', 'slug' => 'women-tops', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1534126511673-b6899657816a?w=600&auto=format&fit=crop&q=80', 'sort_order' => 2],
            ['name' => 'Jeans & Pants', 'slug' => 'women-jeans', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&auto=format&fit=crop&q=80', 'sort_order' => 3],
            ['name' => 'Blazers & Outerwear', 'slug' => 'women-jackets', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=600&auto=format&fit=crop&q=80', 'sort_order' => 4],
            ['name' => 'Knitwear & Sweaters', 'slug' => 'women-knitwear', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600&auto=format&fit=crop&q=80', 'sort_order' => 5],
            ['name' => 'Skirts', 'slug' => 'women-skirts', 'parent' => 'women', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?w=600&auto=format&fit=crop&q=80', 'sort_order' => 6],

            // Accessories
            ['name' => 'Bags & Backpacks', 'slug' => 'accessories-bags', 'parent' => 'accessories', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&auto=format&fit=crop&q=80', 'sort_order' => 1],
            ['name' => 'Scarves & Hats', 'slug' => 'accessories-scarves', 'parent' => 'accessories', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80', 'sort_order' => 2],
            ['name' => 'Leather Goods & Belts', 'slug' => 'accessories-leather', 'parent' => 'accessories', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=600&auto=format&fit=crop&q=80', 'sort_order' => 3],

            // Shoes
            ['name' => 'Sneakers', 'slug' => 'shoes-sneakers', 'parent' => 'shoes', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80', 'sort_order' => 1],
            ['name' => 'Boots & Loafers', 'slug' => 'shoes-boots', 'parent' => 'shoes', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=600&auto=format&fit=crop&q=80', 'sort_order' => 2],
        ];

        foreach ($subs as $s) {
            $parent = $categories[$s['parent']];
            $categories[$s['slug']] = Category::updateOrCreate(['slug' => $s['slug']], [
                'name' => $s['name'],
                'parent_id' => $parent->id,
                'gender' => $s['gender'],
                'image' => $s['image'],
                'sort_order' => $s['sort_order'],
                'is_active' => true,
                'is_featured' => true,
                'seo_title' => "{$s['name']} — WearDay Collection",
                'seo_description' => "Shop curated {$s['name']} crafted from luxury organic fabrics with modern tailored silhouettes.",
            ]);
        }

        return $categories;
    }

    private function seedAttributesAndTags(): array
    {
        // Attributes: Size & Color
        $sizeAttr = ProductAttribute::firstOrCreate(['slug' => 'size'], ['name' => 'Size']);
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $sz) {
            AttributeValue::firstOrCreate(['attribute_id' => $sizeAttr->id, 'value' => $sz]);
        }

        $colorAttr = ProductAttribute::firstOrCreate(['slug' => 'color'], ['name' => 'Color']);
        $colors = [
            ['value' => 'Oatmeal Heather', 'code' => '#e5ded4'],
            ['value' => 'Olive Sage', 'code' => '#5a6855'],
            ['value' => 'Washed Black', 'code' => '#222222'],
            ['value' => 'Pure White', 'code' => '#ffffff'],
            ['value' => 'Deep Navy', 'code' => '#1c2536'],
            ['value' => 'Camel Tan', 'code' => '#c19a6b'],
            ['value' => 'Forest Moss', 'code' => '#354a3b'],
            ['value' => 'Charcoal Melange', 'code' => '#36454f'],
            ['value' => 'Terracotta', 'code' => '#c46851'],
            ['value' => 'Slate Grey', 'code' => '#708090'],
            ['value' => 'Dusty Rose', 'code' => '#dcae96'],
            ['value' => 'Raw Indigo', 'code' => '#1f2e4d'],
        ];
        foreach ($colors as $c) {
            AttributeValue::firstOrCreate(['attribute_id' => $colorAttr->id, 'value' => $c['value']], ['code' => $c['code']]);
        }

        // Tags
        $tagNames = [
            'Organic Cotton', 'Sustainable', 'Water-Resistant', 'Merino Wool',
            'Minimalist', 'Everyday Essential', 'Relaxed Fit', 'Heavyweight',
            'Tailored', 'Breathable Flax', 'Cashmere Blend', 'Japanese Denim',
            'All-Season', 'Wrinkle-Free', 'GOTS Certified', 'Limited Edition',
        ];

        $tags = [];
        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            $tags[$slug] = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        return $tags;
    }

    private function seedProducts(array $brands, array $categories, array $tags): void
    {
        $productsCatalog = [
            [
                'slug' => 'classic-denim-trucker-jacket',
                'name' => 'Classic Selvedge Denim Trucker',
                'sku' => 'CDT-1400',
                'subtitle' => '14oz Japanese red-line selvedge raw denim',
                'description' => '<p>An iconic denim trucker jacket woven on vintage Toyoda shuttle looms in Okayama, Japan. Crafted from 100% long-staple organic cotton dyed with pure natural indigo.</p><p>Features classic front pleats, dual patch chest pockets, custom branded antique brass shank buttons, and internal selvedge ID line along the front placket.</p>',
                'category_slug' => 'men-jackets',
                'brand_slug' => 'urban-thread',
                'gender' => 'men',
                'regular_price' => 148.00,
                'sale_price' => 129.00,
                'cost_price' => 52.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '100% Japanese Selvedge Cotton (14oz)',
                'fit' => 'Standard tailored jacket fit. Sits at the waist. True to size.',
                'details' => [
                    '14oz Okayama raw selvedge denim',
                    'Custom oxidized brass hardware',
                    'Dual welt waist pockets and button-flap chest pockets',
                    'Red-line selvedge detail along interior placket',
                ],
                'care' => [
                    'Wear for 6 months before first soak for authentic custom wear fades',
                    'Hand wash inside out with cold water and gentle detergent',
                    'Hang dry in shade; never tumble dry',
                ],
                'size_guide' => [
                    ['size' => 'S', 'chest' => '38-40"', 'length' => '25.5"', 'sleeve' => '33.5"'],
                    ['size' => 'M', 'chest' => '40-42"', 'length' => '26.2"', 'sleeve' => '34.5"'],
                    ['size' => 'L', 'chest' => '42-44"', 'length' => '27.0"', 'sleeve' => '35.5"'],
                    ['size' => 'XL', 'chest' => '45-47"', 'length' => '27.8"', 'sleeve' => '36.2"'],
                ],
                'outfit_pairings' => ['relaxed-fit-tee', 'straight-leg-selvedge-jeans', 'clean-low-top-leather-sneakers'],
                'seo_title' => 'Classic Selvedge Denim Trucker Jacket — WearDay Menswear',
                'seo_description' => '14oz Japanese red-line selvedge raw denim trucker jacket handcrafted for enduring character.',
                'tags' => ['japanese-denim', 'sustainable', 'everyday-essential'],
                'images' => [
                    'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CDT-RAW-S', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'S', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 15],
                    ['sku' => 'CDT-RAW-M', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'M', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 28],
                    ['sku' => 'CDT-RAW-L', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'L', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 22],
                    ['sku' => 'CDT-RAW-XL', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'XL', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 10],
                    ['sku' => 'CDT-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 18],
                    ['sku' => 'CDT-BLK-L', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'L', 'price' => 129.00, 'compare_at_price' => 148.00, 'stock' => 14],
                ],
            ],
            [
                'slug' => 'oxford-cotton-button-down',
                'name' => 'Tailored Oxford Cotton Button-Down',
                'sku' => 'OCB-1500',
                'subtitle' => 'Combed 2-ply Egyptian cotton oxford weave',
                'description' => '<p>A timeless wardrobe cornerstone redefined for modern versatility. Woven with double-twisted 2-ply yarn for substantial hand-feel, graceful roll, and lasting durability.</p><p>Features a tailored modern fit, soft unlined button-down collar, genuine mother-of-pearl buttons, and rounded barrel cuffs.</p>',
                'category_slug' => 'men-shirts',
                'brand_slug' => 'wearday-studio',
                'gender' => 'men',
                'regular_price' => 68.00,
                'sale_price' => 54.00,
                'cost_price' => 22.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => false,
                'fabric' => '100% Long-Staple Egyptian Cotton',
                'fit' => 'Tailored Classic. Gently tapered through the waist without restricting movement.',
                'details' => [
                    'Substantial 165 GSM pinpoint oxford cloth',
                    'Soft 3-inch button-down collar with natural roll',
                    'Single chest pocket and curved shirt hem',
                    'Cross-stitched natural mother-of-pearl buttons',
                ],
                'care' => [
                    'Machine wash warm with like colors',
                    'Tumble dry low and remove promptly',
                    'Iron warm or steam lightly for crisp finish',
                ],
                'size_guide' => [
                    ['size' => 'S', 'neck' => '14.5-15"', 'chest' => '38-40"', 'sleeve' => '33"'],
                    ['size' => 'M', 'neck' => '15.5-16"', 'chest' => '40-42"', 'sleeve' => '34"'],
                    ['size' => 'L', 'neck' => '16.5-17"', 'chest' => '42-44"', 'sleeve' => '35"'],
                    ['size' => 'XL', 'neck' => '17.5-18"', 'chest' => '45-47"', 'sleeve' => '36"'],
                ],
                'outfit_pairings' => ['tailored-cotton-chino', 'clean-low-top-leather-sneakers', 'suede-chelsea-boots'],
                'seo_title' => 'Tailored Oxford Cotton Button-Down — WearDay',
                'seo_description' => 'Heavyweight 2-ply Egyptian cotton Oxford button-down shirt with mother-of-pearl buttons.',
                'tags' => ['tailored', 'everyday-essential', 'all-season'],
                'images' => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'OCB-WHT-S', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'S', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 20],
                    ['sku' => 'OCB-WHT-M', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'M', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 35],
                    ['sku' => 'OCB-WHT-L', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'L', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 25],
                    ['sku' => 'OCB-BLU-S', 'color' => 'Slate Grey', 'color_hex' => '#708090', 'size' => 'S', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 15],
                    ['sku' => 'OCB-BLU-M', 'color' => 'Slate Grey', 'color_hex' => '#708090', 'size' => 'M', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 30],
                    ['sku' => 'OCB-BLU-L', 'color' => 'Slate Grey', 'color_hex' => '#708090', 'size' => 'L', 'price' => 54.00, 'compare_at_price' => 68.00, 'stock' => 20],
                ],
            ],
            [
                'slug' => 'brushed-flannel-overshirt',
                'name' => 'Heavyweight Brushed Twill Overshirt',
                'sku' => 'BFO-1600',
                'subtitle' => '300 GSM dense organic cotton twill',
                'description' => '<p>Engineered to bridge the transition between crisp autumn winds and indoor comfort. Crafted from heavyweight brushed cotton twill with an ultra-soft peached hand feel.</p><p>Features dual reinforced chest utility pockets with pencil slots, a clean straight hem designed to be worn untucked, and durable corozo nut buttons.</p>',
                'category_slug' => 'men-shirts',
                'brand_slug' => 'urban-thread',
                'gender' => 'men',
                'regular_price' => 84.00,
                'sale_price' => null,
                'cost_price' => 30.00,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => '100% Organic Heavyweight Cotton Twill',
                'fit' => 'Relaxed Overshirt Fit. Cut roomier in the body and arms to layer comfortably over a tee or hoodie.',
                'details' => [
                    '300 GSM brushed organic cotton twill',
                    'Double-needle chainstitched seams for durability',
                    'Side-seam subtle stash pockets',
                    'Custom matte-finish natural corozo buttons',
                ],
                'care' => [
                    'Machine wash cold inside out',
                    'Line dry in shade to preserve brushed nap',
                    'Warm iron on reverse side',
                ],
                'size_guide' => [
                    ['size' => 'S', 'chest' => '40-42"', 'length' => '28.0"', 'sleeve' => '34.0"'],
                    ['size' => 'M', 'chest' => '42-44"', 'length' => '28.8"', 'sleeve' => '35.0"'],
                    ['size' => 'L', 'chest' => '44-46"', 'length' => '29.5"', 'sleeve' => '36.0"'],
                    ['size' => 'XL', 'chest' => '47-49"', 'length' => '30.2"', 'sleeve' => '36.8"'],
                ],
                'outfit_pairings' => ['relaxed-fit-tee', 'straight-leg-selvedge-jeans', 'suede-chelsea-boots'],
                'seo_title' => 'Heavyweight Brushed Twill Overshirt — Urban Thread',
                'seo_description' => 'Heavyweight 300 GSM organic cotton overshirt with utility pockets and straight cut hem.',
                'tags' => ['heavyweight', 'everyday-essential', 'sustainable'],
                'images' => [
                    'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'BFO-OLI-S', 'color' => 'Olive Sage', 'color_hex' => '#5a6855', 'size' => 'S', 'price' => 84.00, 'stock' => 12],
                    ['sku' => 'BFO-OLI-M', 'color' => 'Olive Sage', 'color_hex' => '#5a6855', 'size' => 'M', 'price' => 84.00, 'stock' => 24],
                    ['sku' => 'BFO-OLI-L', 'color' => 'Olive Sage', 'color_hex' => '#5a6855', 'size' => 'L', 'price' => 84.00, 'stock' => 18],
                    ['sku' => 'BFO-CHA-M', 'color' => 'Charcoal Melange', 'color_hex' => '#36454f', 'size' => 'M', 'price' => 84.00, 'stock' => 22],
                    ['sku' => 'BFO-CHA-L', 'color' => 'Charcoal Melange', 'color_hex' => '#36454f', 'size' => 'L', 'price' => 84.00, 'stock' => 16],
                ],
            ],
            [
                'slug' => 'merino-wool-crewneck-sweater',
                'name' => 'Fine-Gauge Merino Crewneck',
                'sku' => 'MCS-1700',
                'subtitle' => '19.5 micron ultra-fine Australian Merino wool',
                'description' => '<p>Knitted from ethically sourced 19.5 micron Australian Merino wool, spun to a lightweight 14-gauge knit that offers exceptional thermal regulation without bulk.</p><p>Naturally breathable, odor-resistant, and machine washable on gentle wool cycle. Finished with finely ribbed collar, cuffs, and hem.</p>',
                'category_slug' => 'men-knitwear',
                'brand_slug' => 'ecoknit',
                'gender' => 'men',
                'regular_price' => 95.00,
                'sale_price' => 79.00,
                'cost_price' => 35.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '100% Extra-Fine Australian Merino Wool',
                'fit' => 'Slim Tailored. Fits cleanly over a shirt or base layer.',
                'details' => [
                    '14-gauge seamless fully-fashioned knit construction',
                    'Natural odor resistance and microclimate temperature regulation',
                    'Ribbed tubular collar with elastane retention',
                    'RWS (Responsible Wool Standard) certified',
                ],
                'care' => [
                    'Machine wash cold on delicate/wool cycle with wool detergent',
                    'Lay flat on towel to dry in shade',
                    'Store folded; do not hang to prevent stretching',
                ],
                'size_guide' => [
                    ['size' => 'S', 'chest' => '37-39"', 'length' => '26.0"', 'sleeve' => '33.5"'],
                    ['size' => 'M', 'chest' => '39-41"', 'length' => '26.8"', 'sleeve' => '34.5"'],
                    ['size' => 'L', 'chest' => '41-43"', 'length' => '27.5"', 'sleeve' => '35.5"'],
                    ['size' => 'XL', 'chest' => '44-46"', 'length' => '28.2"', 'sleeve' => '36.5"'],
                ],
                'outfit_pairings' => ['oxford-cotton-button-down', 'wide-leg-pleated-trousers', 'suede-chelsea-boots'],
                'seo_title' => 'Fine-Gauge Merino Wool Crewneck Sweater — EcoKnit',
                'seo_description' => '19.5 micron ultra-fine Australian Merino wool crewneck sweater with temperature-regulating properties.',
                'tags' => ['merino-wool', 'sustainable', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'MCS-NAV-S', 'color' => 'Deep Navy', 'color_hex' => '#1c2536', 'size' => 'S', 'price' => 79.00, 'compare_at_price' => 95.00, 'stock' => 14],
                    ['sku' => 'MCS-NAV-M', 'color' => 'Deep Navy', 'color_hex' => '#1c2536', 'size' => 'M', 'price' => 79.00, 'compare_at_price' => 95.00, 'stock' => 26],
                    ['sku' => 'MCS-NAV-L', 'color' => 'Deep Navy', 'color_hex' => '#1c2536', 'size' => 'L', 'price' => 79.00, 'compare_at_price' => 95.00, 'stock' => 20],
                    ['sku' => 'MCS-OAT-M', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'M', 'price' => 79.00, 'compare_at_price' => 95.00, 'stock' => 22],
                    ['sku' => 'MCS-OAT-L', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'L', 'price' => 79.00, 'compare_at_price' => 95.00, 'stock' => 18],
                ],
            ],
            [
                'slug' => 'pleated-linen-trouser-shorts',
                'name' => 'Pleated Linen Trouser Shorts',
                'sku' => 'PLT-1800',
                'subtitle' => 'French Normandy flax with elasticated back waist',
                'description' => '<p>Elevated warm-weather tailoring inspired by relaxed Mediterranean summers. Tailored with front single pleats, slash side pockets, and an elasticated waistband with internal drawstrings.</p><p>Woven from pre-washed French flax linen that softens gracefully with every wear and wash.</p>',
                'category_slug' => 'men-shorts',
                'brand_slug' => 'atelier-riviera',
                'gender' => 'men',
                'regular_price' => 58.00,
                'sale_price' => null,
                'cost_price' => 20.00,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => '100% French Normandy Flax Linen',
                'fit' => 'Relaxed Tailored Short. 7-inch inseam hitting right above the knee.',
                'details' => [
                    '200 GSM Normandy pre-washed linen',
                    'Single forward pleats for comfortable seat room',
                    'Concealed horn button and zip fly',
                    'Internal herringbone cotton drawstring',
                ],
                'care' => [
                    'Machine wash gentle in cold water',
                    'Line dry in shade; embrace natural linen texture',
                    'Medium iron if crisp look is preferred',
                ],
                'size_guide' => [
                    ['size' => 'S', 'waist' => '30-31"', 'inseam' => '7.0"', 'leg_opening' => '21.5"'],
                    ['size' => 'M', 'waist' => '32-33"', 'inseam' => '7.2"', 'leg_opening' => '22.0"'],
                    ['size' => 'L', 'waist' => '34-35"', 'inseam' => '7.5"', 'leg_opening' => '22.8"'],
                    ['size' => 'XL', 'waist' => '36-38"', 'inseam' => '7.8"', 'leg_opening' => '23.5"'],
                ],
                'outfit_pairings' => ['linen-blend-shirt', 'relaxed-fit-tee', 'clean-low-top-leather-sneakers'],
                'seo_title' => 'Pleated Linen Trouser Shorts — Atelier Riviera',
                'seo_description' => 'French flax linen pleated shorts with 7-inch inseam and elasticated comfort waist.',
                'tags' => ['breathable-flax', 'sustainable', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1542272604-780c96856592?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'PLT-OAT-S', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'S', 'price' => 58.00, 'stock' => 16],
                    ['sku' => 'PLT-OAT-M', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'M', 'price' => 58.00, 'stock' => 28],
                    ['sku' => 'PLT-OAT-L', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'L', 'price' => 58.00, 'stock' => 20],
                    ['sku' => 'PLT-TER-M', 'color' => 'Terracotta', 'color_hex' => '#c46851', 'size' => 'M', 'price' => 58.00, 'stock' => 18],
                    ['sku' => 'PLT-TER-L', 'color' => 'Terracotta', 'color_hex' => '#c46851', 'size' => 'L', 'price' => 58.00, 'stock' => 14],
                ],
            ],
            [
                'slug' => 'structured-linen-trench-coat',
                'name' => 'Structured Double-Breasted Linen Trench',
                'sku' => 'SLT-1900',
                'subtitle' => 'Heavyweight European flax with storm flaps & horn buttons',
                'description' => '<p>A modern architectural reinterpretation of the iconic military trench. Tailored from substantial 280 GSM European flax linen with a fluid drape and structured shoulders.</p><p>Designed with a generous notched lapel, back rain shield, adjustable belt with horn buckle, and deep welt pockets.</p>',
                'category_slug' => 'women-jackets',
                'brand_slug' => 'artisan-atelier',
                'gender' => 'women',
                'regular_price' => 220.00,
                'sale_price' => 189.00,
                'cost_price' => 78.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => '100% European Flax Linen (280 GSM)',
                'fit' => 'Relaxed Oversized Trench. Sits mid-calf. Cinch at waist for defined silhouette.',
                'details' => [
                    'Double-breasted front with real horn buttons',
                    'Removable tie belt with stitched belt loops',
                    'Back deep inverted pleat for graceful movement',
                    'Unlined body with bound cotton seams for breathable warm seasons',
                ],
                'care' => [
                    'Dry clean recommended to maintain tailored shoulder structure',
                    'Warm steam to relax travel wrinkles',
                ],
                'size_guide' => [
                    ['size' => 'XS', 'bust' => '32-34"', 'length' => '44.5"', 'sleeve' => '31.0"'],
                    ['size' => 'S', 'bust' => '34-36"', 'length' => '45.2"', 'sleeve' => '31.8"'],
                    ['size' => 'M', 'bust' => '36-38"', 'length' => '46.0"', 'sleeve' => '32.5"'],
                    ['size' => 'L', 'bust' => '39-41"', 'length' => '46.8"', 'sleeve' => '33.2"'],
                ],
                'outfit_pairings' => ['silk-slip-dress', 'clean-low-top-leather-sneakers', 'minimalist-leather-tote'],
                'seo_title' => 'Structured Double-Breasted Linen Trench Coat — WearDay',
                'seo_description' => 'Heavyweight 280 GSM European flax double-breasted trench coat with storm flaps.',
                'tags' => ['breathable-flax', 'tailored', 'sustainable'],
                'images' => [
                    'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'SLT-CAM-XS', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'XS', 'price' => 189.00, 'compare_at_price' => 220.00, 'stock' => 10],
                    ['sku' => 'SLT-CAM-S', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'S', 'price' => 189.00, 'compare_at_price' => 220.00, 'stock' => 18],
                    ['sku' => 'SLT-CAM-M', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'M', 'price' => 189.00, 'compare_at_price' => 220.00, 'stock' => 24],
                    ['sku' => 'SLT-CAM-L', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'L', 'price' => 189.00, 'compare_at_price' => 220.00, 'stock' => 12],
                    ['sku' => 'SLT-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 189.00, 'compare_at_price' => 220.00, 'stock' => 16],
                ],
            ],
            [
                'slug' => 'satin-bias-cut-skirt',
                'name' => 'Satin Bias-Cut Midi Skirt',
                'sku' => 'SBS-2000',
                'subtitle' => 'Fluid Japanese crepe-back satin with subtle sheen',
                'description' => '<p>Cut on the bias to drape effortlessly against the contours of the body. Woven from luxury heavyweight crepe-back satin that glides gracefully with every step.</p><p>Features a hidden elasticated waistband for all-day ease and delicate baby-hemed edges.</p>',
                'category_slug' => 'women-skirts',
                'brand_slug' => 'minimalist-co',
                'gender' => 'women',
                'regular_price' => 78.00,
                'sale_price' => 64.00,
                'cost_price' => 26.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '82% Triacetate, 18% Recycled Poly Satin',
                'fit' => 'Bias Cut Midi. Flutes gently toward hem. True to size.',
                'details' => [
                    'Cut diagonally on the bias for fluid movement',
                    'Concealed smooth elastic grosgrain waistband',
                    'Mid-calf length hitting right below the knee',
                    'Anti-static lining that prevents clinging',
                ],
                'care' => [
                    'Hand wash cold with silk detergent or dry clean',
                    'Hang dry in shade',
                    'Cool iron on reverse side under press cloth',
                ],
                'size_guide' => [
                    ['size' => 'XS', 'waist' => '24-25"', 'hips' => '34-36"', 'length' => '32.5"'],
                    ['size' => 'S', 'waist' => '26-27"', 'hips' => '36-38"', 'length' => '33.0"'],
                    ['size' => 'M', 'waist' => '28-29"', 'hips' => '38-40"', 'length' => '33.5"'],
                    ['size' => 'L', 'waist' => '30-32"', 'hips' => '41-43"', 'length' => '34.0"'],
                ],
                'outfit_pairings' => ['chunky-merino-cardigan', 'clean-low-top-leather-sneakers', 'oversized-wool-blazer'],
                'seo_title' => 'Satin Bias-Cut Midi Skirt — Minimalist Co',
                'seo_description' => 'Heavyweight Japanese crepe satin midi skirt cut on the bias with concealed elastic waist.',
                'tags' => ['minimalist', 'everyday-essential', 'sustainable'],
                'images' => [
                    'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'SBS-ROS-XS', 'color' => 'Dusty Rose', 'color_hex' => '#dcae96', 'size' => 'XS', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 14],
                    ['sku' => 'SBS-ROS-S', 'color' => 'Dusty Rose', 'color_hex' => '#dcae96', 'size' => 'S', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 24],
                    ['sku' => 'SBS-ROS-M', 'color' => 'Dusty Rose', 'color_hex' => '#dcae96', 'size' => 'M', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 28],
                    ['sku' => 'SBS-BLK-S', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'S', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 20],
                    ['sku' => 'SBS-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 30],
                    ['sku' => 'SBS-BLK-L', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'L', 'price' => 64.00, 'compare_at_price' => 78.00, 'stock' => 15],
                ],
            ],
            [
                'slug' => 'cashmere-turtleneck-sweater',
                'name' => 'Mongolian Cashmere Turtleneck',
                'sku' => 'CTS-2100',
                'subtitle' => '100% Grade-A 2-ply Mongolian cashmere',
                'description' => '<p>The pinnacle of pure cold-weather indulgence. Crafted from Grade-A Mongolian cashmere with fibers measuring over 34mm in length to resist pilling and retain cloud-like softness.</p><p>Features a cozy fold-over turtleneck, drop-shoulder seams, and substantial ribbing at the hem and cuffs.</p>',
                'category_slug' => 'women-knitwear',
                'brand_slug' => 'ecoknit',
                'gender' => 'women',
                'regular_price' => 175.00,
                'sale_price' => 149.00,
                'cost_price' => 65.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '100% Grade-A Mongolian Cashmere (2-ply, 12-gauge)',
                'fit' => 'Relaxed Easy Fit. Boxy through the torso with ribbed drape.',
                'details' => [
                    'Long-staple 34mm Grade-A fibers for maximum durability and low pill',
                    'Generous rib turtleneck that stays up without constricting',
                    'Side vents at hem for graceful pairing over high-rise trousers',
                    'OEKO-TEX Standard 100 non-toxic certified dye process',
                ],
                'care' => [
                    'Hand wash in tepid water with cashmere wash',
                    'Press out excess moisture in towel; dry flat',
                    'Comb occasionally with cedar cashmere comb',
                ],
                'size_guide' => [
                    ['size' => 'XS', 'bust' => '32-34"', 'length' => '23.0"', 'sleeve' => '30.5"'],
                    ['size' => 'S', 'bust' => '34-36"', 'length' => '23.5"', 'sleeve' => '31.2"'],
                    ['size' => 'M', 'bust' => '36-38"', 'length' => '24.2"', 'sleeve' => '32.0"'],
                    ['size' => 'L', 'bust' => '39-41"', 'length' => '25.0"', 'sleeve' => '32.8"'],
                ],
                'outfit_pairings' => ['satin-bias-cut-skirt', 'wide-leg-pleated-trousers', 'suede-chelsea-boots'],
                'seo_title' => 'Grade-A Mongolian Cashmere Turtleneck — EcoKnit',
                'seo_description' => '100% Grade-A 2-ply Mongolian cashmere turtleneck sweater with ribbed cuffs and drop shoulder.',
                'tags' => ['cashmere-blend', 'sustainable', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CTS-OAT-XS', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'XS', 'price' => 149.00, 'compare_at_price' => 175.00, 'stock' => 12],
                    ['sku' => 'CTS-OAT-S', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'S', 'price' => 149.00, 'compare_at_price' => 175.00, 'stock' => 20],
                    ['sku' => 'CTS-OAT-M', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'M', 'price' => 149.00, 'compare_at_price' => 175.00, 'stock' => 25],
                    ['sku' => 'CTS-CHA-S', 'color' => 'Charcoal Melange', 'color_hex' => '#36454f', 'size' => 'S', 'price' => 149.00, 'compare_at_price' => 175.00, 'stock' => 15],
                    ['sku' => 'CTS-CHA-M', 'color' => 'Charcoal Melange', 'color_hex' => '#36454f', 'size' => 'M', 'price' => 149.00, 'compare_at_price' => 175.00, 'stock' => 22],
                ],
            ],
            [
                'slug' => 'tailored-linen-vest-waistcoat',
                'name' => 'Tailored Linen Waistcoat Vest',
                'sku' => 'TLV-2200',
                'subtitle' => 'European flax linen with adjustable back cinch',
                'description' => '<p>A sharp sartorial statement designed to be worn solo against bare skin or layered beneath unstructured blazers. Tailored with front darts that sculpt a sleek silhouette.</p><p>Features a pointed hem, real horn buttons, and an adjustable back belt cinch with antique silver slide buckle.</p>',
                'category_slug' => 'women-tops',
                'brand_slug' => 'wearday-studio',
                'gender' => 'women',
                'regular_price' => 64.00,
                'sale_price' => null,
                'cost_price' => 22.00,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => '100% European Flax Linen; 100% Organic Cotton Lining',
                'fit' => 'Tailored Fitted Silhouette. Sits at natural waistline.',
                'details' => [
                    'Front four-button closure with genuine horn buttons',
                    'Double welt jetted decorative front pockets',
                    'Full interior organic cotton lining for complete opacity',
                    'Back cinch buckle for customizable fit',
                ],
                'care' => [
                    'Hand wash cold or gentle dry clean',
                    'Reshape and dry flat',
                    'Warm iron with steam',
                ],
                'size_guide' => [
                    ['size' => 'XS', 'bust' => '31-33"', 'waist' => '24-26"', 'length' => '18.5"'],
                    ['size' => 'S', 'bust' => '33-35"', 'waist' => '26-28"', 'length' => '19.0"'],
                    ['size' => 'M', 'bust' => '35-37"', 'waist' => '28-30"', 'length' => '19.5"'],
                    ['size' => 'L', 'bust' => '38-40"', 'waist' => '31-33"', 'length' => '20.2"'],
                ],
                'outfit_pairings' => ['wide-leg-pleated-trousers', 'oversized-wool-blazer', 'clean-low-top-leather-sneakers'],
                'seo_title' => 'Tailored Linen Waistcoat Vest — WearDay Studio',
                'seo_description' => 'European flax linen waistcoat with horn buttons and tailored front darts.',
                'tags' => ['tailored', 'breathable-flax', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1534126511673-b6899657816a?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'TLV-WHT-XS', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'XS', 'price' => 64.00, 'stock' => 14],
                    ['sku' => 'TLV-WHT-S', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'S', 'price' => 64.00, 'stock' => 22],
                    ['sku' => 'TLV-WHT-M', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'M', 'price' => 64.00, 'stock' => 26],
                    ['sku' => 'TLV-BLK-S', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'S', 'price' => 64.00, 'stock' => 18],
                    ['sku' => 'TLV-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 64.00, 'stock' => 24],
                ],
            ],
            [
                'slug' => 'high-rise-wide-leg-jeans',
                'name' => 'High-Rise Organic Wide-Leg Jeans',
                'sku' => 'HRW-2300',
                'subtitle' => '13oz GOTS certified organic rigid denim',
                'description' => '<p>Crafted with a flattering ultra-high rise and an elongated wide leg that creates an effortlessly statuesque silhouette. Made from pure organic cotton denim that molds uniquely to your shape over time.</p><p>Features classic five-pocket styling, contrast tobacco stitching, and eco-finished brass hardware.</p>',
                'category_slug' => 'women-jeans',
                'brand_slug' => 'urban-thread',
                'gender' => 'women',
                'regular_price' => 88.00,
                'sale_price' => 74.00,
                'cost_price' => 32.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '100% GOTS Certified Organic Cotton (13oz)',
                'fit' => 'High Rise (11.5"). Fitted through hips, opening into relaxed wide leg with 32" inseam.',
                'details' => [
                    '13oz non-stretch rigid organic cotton',
                    '11.5-inch rise hitting above natural waist',
                    'Subtle stone wash for vintage character without harsh chemicals',
                    'Recycled paper Jacron back waist patch',
                ],
                'care' => [
                    'Wash cold inside out every 5-10 wears',
                    'Hang dry to protect denim fibers and color depth',
                    'Never use bleach or fabric softeners',
                ],
                'size_guide' => [
                    ['size' => '25', 'waist' => '25"', 'hips' => '35"', 'rise' => '11.2"', 'inseam' => '32"'],
                    ['size' => '26', 'waist' => '26"', 'hips' => '36"', 'rise' => '11.5"', 'inseam' => '32"'],
                    ['size' => '27', 'waist' => '27"', 'hips' => '37"', 'rise' => '11.5"', 'inseam' => '32"'],
                    ['size' => '28', 'waist' => '28"', 'hips' => '38"', 'rise' => '11.8"', 'inseam' => '32"'],
                    ['size' => '29', 'waist' => '29"', 'hips' => '39"', 'rise' => '12.0"', 'inseam' => '32"'],
                    ['size' => '30', 'waist' => '30"', 'hips' => '40"', 'rise' => '12.2"', 'inseam' => '32"'],
                ],
                'outfit_pairings' => ['relaxed-fit-tee', 'clean-low-top-leather-sneakers', 'oversized-wool-blazer'],
                'seo_title' => 'High-Rise Organic Wide-Leg Jeans — WearDay',
                'seo_description' => '13oz organic cotton high-rise wide-leg denim jeans with vintage stone wash.',
                'tags' => ['sustainable', 'everyday-essential', 'organic-cotton'],
                'images' => [
                    'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1582418702059-97ebafb35d09?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'HRW-IND-S', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'S', 'price' => 74.00, 'compare_at_price' => 88.00, 'stock' => 16],
                    ['sku' => 'HRW-IND-M', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'M', 'price' => 74.00, 'compare_at_price' => 88.00, 'stock' => 28],
                    ['sku' => 'HRW-IND-L', 'color' => 'Raw Indigo', 'color_hex' => '#1f2e4d', 'size' => 'L', 'price' => 74.00, 'compare_at_price' => 88.00, 'stock' => 20],
                    ['sku' => 'HRW-WHT-S', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'S', 'price' => 74.00, 'compare_at_price' => 88.00, 'stock' => 14],
                    ['sku' => 'HRW-WHT-M', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'M', 'price' => 74.00, 'compare_at_price' => 88.00, 'stock' => 22],
                ],
            ],
            [
                'slug' => 'minimalist-crossbody-camera-bag',
                'name' => 'Italian Leather Crossbody Camera Bag',
                'sku' => 'MCB-2400',
                'subtitle' => 'Vegetable-tanned Tuscan calfskin with solid brass zip',
                'description' => '<p>A compact, architectural day bag handcrafted by master artisans in Florence. Cut from full-grain vegetable-tanned Tuscan leather that develops a lustrous golden patina with daily handling.</p><p>Features a spacious main compartment lined in organic cotton twill, an interior slip card pocket, and an adjustable shoulder strap with beveled edge finishing.</p>',
                'category_slug' => 'accessories-bags',
                'brand_slug' => 'artisan-atelier',
                'gender' => 'unisex',
                'regular_price' => 135.00,
                'sale_price' => 115.00,
                'cost_price' => 45.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => '100% Full-Grain Vegetable-Tanned Italian Calfskin',
                'fit' => 'Compact Crossbody. Measures 8.5" W x 6.0" H x 2.8" D.',
                'details' => [
                    'Hand-painted edges and burnished Italian leather',
                    'Swiss-engineered Raccagni solid brass zipper',
                    'Adjustable shoulder strap with 20" to 24" drop',
                    'Protective dust bag included',
                ],
                'care' => [
                    'Condition with natural beeswax leather cream every 6 months',
                    'Avoid prolonged rain exposure; pat dry with cotton cloth',
                    'Store in provided protective dust bag when not in use',
                ],
                'size_guide' => [
                    ['dimension' => 'Width', 'measurement' => '8.5 inches'],
                    ['dimension' => 'Height', 'measurement' => '6.0 inches'],
                    ['dimension' => 'Depth', 'measurement' => '2.8 inches'],
                ],
                'outfit_pairings' => ['classic-denim-trucker-jacket', 'relaxed-fit-tee', 'clean-low-top-leather-sneakers'],
                'seo_title' => 'Italian Leather Crossbody Camera Bag — Artisan Atelier',
                'seo_description' => 'Handcrafted Tuscan vegetable-tanned leather camera crossbody bag with solid brass zipper.',
                'tags' => ['sustainable', 'everyday-essential', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'MCB-BLK-OS', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 115.00, 'compare_at_price' => 135.00, 'stock' => 18],
                    ['sku' => 'MCB-CAM-OS', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'M', 'price' => 115.00, 'compare_at_price' => 135.00, 'stock' => 24],
                    ['sku' => 'MCB-TER-OS', 'color' => 'Terracotta', 'color_hex' => '#c46851', 'size' => 'M', 'price' => 115.00, 'compare_at_price' => 135.00, 'stock' => 12],
                ],
            ],
            [
                'slug' => 'ribbed-cashmere-scarf',
                'name' => 'Heavy Ribbed Cashmere Scarf',
                'sku' => 'RCS-2500',
                'subtitle' => '7-gauge dense Mongolian cashmere with fringeless edges',
                'description' => '<p>A substantial 200cm long winter scarf offering enveloping warmth and tactile richness. Knitted with chunky 7-gauge half-cardigan ribs from un-dyed natural Mongolian cashmere.</p><p>Finished with clean modern tubular edges without tassels for a sleek, contemporary silhouette.</p>',
                'category_slug' => 'accessories-scarves',
                'brand_slug' => 'ecoknit',
                'gender' => 'unisex',
                'regular_price' => 110.00,
                'sale_price' => null,
                'cost_price' => 42.00,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => '100% Grade-A Mongolian Cashmere (7-gauge rib)',
                'fit' => 'Generous Dimensions. Length: 78" (200cm), Width: 14" (35cm).',
                'details' => [
                    'Dense 7-gauge ribbed knit with incredible warmth',
                    '78-inch length allows double wrapping or easy Parisian knot',
                    'Clean selvedge edges without fringes',
                    'Ethically gathered and OEKO-TEX certified',
                ],
                'care' => [
                    'Hand wash cold with wool wash; roll in dry towel',
                    'Dry flat on horizontal surface',
                    'Store folded in cedar wardrobe drawer',
                ],
                'size_guide' => [
                    ['dimension' => 'Length', 'measurement' => '78 inches (200 cm)'],
                    ['dimension' => 'Width', 'measurement' => '14 inches (35 cm)'],
                ],
                'outfit_pairings' => ['wool-cashmere-overcoat', 'merino-wool-crewneck-sweater', 'suede-chelsea-boots'],
                'seo_title' => 'Heavy Ribbed Cashmere Scarf — EcoKnit',
                'seo_description' => 'Chunky 7-gauge Mongolian cashmere ribbed winter scarf with clean fringeless edges.',
                'tags' => ['cashmere-blend', 'sustainable', 'minimalist'],
                'images' => [
                    'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'RCS-OAT-OS', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'M', 'price' => 110.00, 'stock' => 20],
                    ['sku' => 'RCS-CHA-OS', 'color' => 'Charcoal Melange', 'color_hex' => '#36454f', 'size' => 'M', 'price' => 110.00, 'stock' => 18],
                    ['sku' => 'RCS-FOR-OS', 'color' => 'Forest Moss', 'color_hex' => '#354a3b', 'size' => 'M', 'price' => 110.00, 'stock' => 15],
                ],
            ],
            [
                'slug' => 'clean-low-top-leather-sneakers',
                'name' => 'Artisanal Low-Top Leather Sneakers',
                'sku' => 'CLS-2600',
                'subtitle' => 'Italian Nappa leather with Margom stitched rubber cupsole',
                'description' => '<p>The definitive luxury minimalist sneaker. Handcrafted in Civitanova Marche, Italy, using buttery-soft full-grain Nappa calf leather and a reinforced Margom Italian rubber cupsole stitched 360-degrees around the perimeter.</p><p>Fully lined in calfskin with a cushioned memory foam insole and waxed cotton laces for supreme comfort from the very first stride.</p>',
                'category_slug' => 'shoes-sneakers',
                'brand_slug' => 'artisan-atelier',
                'gender' => 'unisex',
                'regular_price' => 165.00,
                'sale_price' => 139.00,
                'cost_price' => 58.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => true,
                'fabric' => 'Full-Grain Italian Calfskin Nappa; Margom Rubber Sole',
                'fit' => 'Sleek Minimalist Sneaker. Runs true to European sizing. Size down if between sizes.',
                'details' => [
                    'Buttery Italian calfskin upper and lining',
                    '360-degree stitched Margom natural rubber sole',
                    'Ergonomic removable memory foam calfskin insole',
                    'Tonal waxed Italian cotton laces',
                ],
                'care' => [
                    'Clean with damp microfiber cloth and mild sneaker cleaner',
                    'Treat leather upper with neutral leather conditioner',
                    'Insert wooden shoe trees after wear to maintain crisp silhouette',
                ],
                'size_guide' => [
                    ['size' => 'EU 40', 'us_men' => '7.5', 'us_women' => '9.0', 'insole_cm' => '26.0'],
                    ['size' => 'EU 41', 'us_men' => '8.5', 'us_women' => '10.0', 'insole_cm' => '26.7'],
                    ['size' => 'EU 42', 'us_men' => '9.5', 'us_women' => '11.0', 'insole_cm' => '27.3'],
                    ['size' => 'EU 43', 'us_men' => '10.5', 'us_women' => '12.0', 'insole_cm' => '28.0'],
                    ['size' => 'EU 44', 'us_men' => '11.5', 'us_women' => '13.0', 'insole_cm' => '28.7'],
                ],
                'outfit_pairings' => ['straight-leg-selvedge-jeans', 'tailored-cotton-chino', 'relaxed-fit-tee'],
                'seo_title' => 'Artisanal Italian Low-Top Leather Sneakers — Artisan Atelier',
                'seo_description' => 'Handcrafted Italian Nappa leather low-top minimalist sneakers with Margom cupsole.',
                'tags' => ['minimalist', 'everyday-essential', 'tailored'],
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CLS-WHT-S', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'S', 'price' => 139.00, 'compare_at_price' => 165.00, 'stock' => 18],
                    ['sku' => 'CLS-WHT-M', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'M', 'price' => 139.00, 'compare_at_price' => 165.00, 'stock' => 30],
                    ['sku' => 'CLS-WHT-L', 'color' => 'Pure White', 'color_hex' => '#ffffff', 'size' => 'L', 'price' => 139.00, 'compare_at_price' => 165.00, 'stock' => 24],
                    ['sku' => 'CLS-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 139.00, 'compare_at_price' => 165.00, 'stock' => 22],
                    ['sku' => 'CLS-BLK-L', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'L', 'price' => 139.00, 'compare_at_price' => 165.00, 'stock' => 16],
                ],
            ],
            [
                'slug' => 'suede-chelsea-boots',
                'name' => 'Goodyear-Welted Suede Chelsea Boots',
                'sku' => 'SCB-2700',
                'subtitle' => 'Water-repellent European calf suede with Dainite rubber sole',
                'description' => '<p>An enduring icon of sartorial footwear engineered with traditional Goodyear welted construction that can be re-soled for decades of service.</p><p>Crafted from premium water-repellent calf suede with heavy-duty elastic side gussets, dual branded woven pull tabs, and British studded Dainite rubber soles for steady all-weather traction.</p>',
                'category_slug' => 'shoes-boots',
                'brand_slug' => 'artisan-atelier',
                'gender' => 'men',
                'regular_price' => 210.00,
                'sale_price' => 185.00,
                'cost_price' => 80.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => true,
                'fabric' => 'Water-Repellent European Calf Suede; Dainite Studded Sole',
                'fit' => 'Classic Chelsea Boot. Runs true to size.',
                'details' => [
                    'Traditional 360-degree Goodyear welt construction',
                    'Durable British Dainite studded rubber outsole for wet grip',
                    'Full calfskin glove leather lining for breathability',
                    'Heavy-duty elastic webbing gusset and twin pull loops',
                ],
                'care' => [
                    'Brush with brass or crepe suede brush to restore nap',
                    'Spray seasonally with non-silicone suede water protector',
                    'Allow to dry naturally away from direct radiators if wet',
                ],
                'size_guide' => [
                    ['size' => 'EU 41', 'us_men' => '8.5', 'insole_cm' => '27.0'],
                    ['size' => 'EU 42', 'us_men' => '9.5', 'insole_cm' => '27.6'],
                    ['size' => 'EU 43', 'us_men' => '10.5', 'insole_cm' => '28.3'],
                    ['size' => 'EU 44', 'us_men' => '11.5', 'insole_cm' => '29.0'],
                ],
                'outfit_pairings' => ['straight-leg-selvedge-jeans', 'wool-cashmere-overcoat', 'merino-wool-crewneck-sweater'],
                'seo_title' => 'Goodyear-Welted Suede Chelsea Boots — Artisan Atelier',
                'seo_description' => 'Water-repellent calf suede Chelsea boots with Goodyear welt and Dainite studded sole.',
                'tags' => ['water-resistant', 'tailored', 'all-season'],
                'images' => [
                    'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'SCB-CAM-M', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'M', 'price' => 185.00, 'compare_at_price' => 210.00, 'stock' => 16],
                    ['sku' => 'SCB-CAM-L', 'color' => 'Camel Tan', 'color_hex' => '#c19a6b', 'size' => 'L', 'price' => 185.00, 'compare_at_price' => 210.00, 'stock' => 22],
                    ['sku' => 'SCB-BLK-M', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'M', 'price' => 185.00, 'compare_at_price' => 210.00, 'stock' => 18],
                    ['sku' => 'SCB-BLK-L', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'L', 'price' => 185.00, 'compare_at_price' => 210.00, 'stock' => 15],
                ],
            ],
        ];

        foreach ($productsCatalog as $pData) {
            $cat = $categories[$pData['category_slug']] ?? Category::first();
            $brand = $brands[$pData['brand_slug']] ?? Brand::first();

            $variants = $pData['variants'];
            $images = $pData['images'];
            $tagSlugs = $pData['tags'] ?? [];
            unset($pData['variants'], $pData['images'], $pData['tags'], $pData['category_slug'], $pData['brand_slug']);

            $pData['category_id'] = $cat->id;
            $pData['brand_id'] = $brand->id;
            $pData['type'] = 'variable';
            $pData['is_active'] = true;

            $product = Product::updateOrCreate(['slug' => $pData['slug']], $pData);

            // Tags
            $tagIds = [];
            foreach ($tagSlugs as $tSlug) {
                if (isset($tags[$tSlug])) {
                    $tagIds[] = $tags[$tSlug]->id;
                }
            }
            $product->tags()->syncWithoutDetaching($tagIds);

            // Images
            foreach ($images as $index => $imageUrl) {
                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'url' => $imageUrl],
                    [
                        'alt' => "{$product->name} - Photo " . ($index + 1),
                        'sort_order' => $index,
                        'is_primary' => ($index === 0),
                    ]
                );
            }

            // Variants & Inventory
            foreach ($variants as $varData) {
                $stock = $varData['stock'];
                unset($varData['stock']);

                $varData['product_id'] = $product->id;
                $varData['cost_price'] = $pData['cost_price'];
                $varData['is_active'] = true;
                $varData['stock'] = $stock;

                $variant = ProductVariant::updateOrCreate(['sku' => $varData['sku']], $varData);

                // Inventory
                $inventory = Inventory::updateOrCreate(
                    ['product_id' => $product->id, 'variant_id' => $variant->id],
                    [
                        'quantity' => $stock,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 5,
                    ]
                );

                // Initial Movement
                InventoryMovement::firstOrCreate(
                    [
                        'inventory_id' => $inventory->id,
                        'type' => 'initial',
                    ],
                    [
                        'variant_id' => $variant->id,
                        'quantity_change' => $stock,
                        'quantity_before' => 0,
                        'quantity_after' => $stock,
                        'notes' => 'Catalog population inventory stock load',
                    ]
                );
            }
        }
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'title' => 'The Spring/Summer Edit',
                'subtitle' => 'Architectural silhouettes and featherweight flax linens designed for balmy days.',
                'image_desktop' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                'button_text' => 'Explore the Collection',
                'button_url' => '/shop',
                'position' => 'hero_main',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Modern Tailored Essentials',
                'subtitle' => 'Everyday fundamentals engineered with organic cotton, Italian wool, and Japanese selvedge.',
                'image_desktop' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=1600&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
                'button_text' => 'Shop Menswear',
                'button_url' => '/shop?gender=men',
                'position' => 'hero_main',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Artisanal Footwear & Leather',
                'subtitle' => 'Handcrafted low-top calfskin sneakers and Tuscan vegetable-tanned bags.',
                'image_desktop' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1600&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                'button_text' => 'Discover Shoes & Bags',
                'button_url' => '/shop?category=shoes',
                'position' => 'hero_main',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Complimentary Worldwide Ground Shipping',
                'subtitle' => 'On all orders above $50. Seamless 30-day returns and exchanges guaranteed.',
                'image_desktop' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=1200&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80',
                'button_text' => 'Learn More',
                'button_url' => '/shop',
                'position' => 'promo_strip',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Mid-Season Wardrobe Savings: Use Code FASHION20',
                'subtitle' => 'Save 20% on orders exceeding $100 across all in-stock seasonal garments.',
                'image_desktop' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1200&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&auto=format&fit=crop&q=80',
                'button_text' => 'Claim 20% Off',
                'button_url' => '/shop',
                'position' => 'promo_strip',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'The Normandy Linen Edit',
                'subtitle' => 'Light, breathable, and effortlessly cool summer separates.',
                'image_desktop' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=1200&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&auto=format&fit=crop&q=80',
                'button_text' => 'Shop Linen',
                'button_url' => '/shop?tag=breathable-flax',
                'position' => 'collection_banner',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Modern Minimalist Tailoring',
                'subtitle' => 'Architectural blazers, tailored trousers, and silk slips.',
                'image_desktop' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=1200&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=600&auto=format&fit=crop&q=80',
                'button_text' => 'Shop Tailoring',
                'button_url' => '/shop?tag=tailored',
                'position' => 'collection_banner',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Pure Mongolian Cashmere',
                'subtitle' => 'Cloud-soft knitwear responsibly sourced and crafted to endure.',
                'image_desktop' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=1200&auto=format&fit=crop&q=80',
                'image_mobile' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600&auto=format&fit=crop&q=80',
                'button_text' => 'Shop Cashmere',
                'button_url' => '/shop?tag=cashmere-blend',
                'position' => 'collection_banner',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $b) {
            Banner::updateOrCreate(['title' => $b['title']], $b);
        }
    }

    private function seedCoupons(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'description' => '10% off your entire first fashion order',
                'type' => 'percentage',
                'value' => 10.00,
                'min_spend' => 30.00,
                'max_discount' => 50.00,
                'usage_limit_total' => 1000,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'FASHION20',
                'description' => '20% VIP seasonal shopping discount on orders over $100',
                'type' => 'percentage',
                'value' => 20.00,
                'min_spend' => 100.00,
                'max_discount' => 80.00,
                'usage_limit_total' => 500,
                'usage_limit_per_customer' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'SAVE15',
                'description' => '$15 instant cash credit on orders over $75',
                'type' => 'fixed',
                'value' => 15.00,
                'min_spend' => 75.00,
                'max_discount' => null,
                'usage_limit_total' => 200,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'AUTUMN25',
                'description' => '25% off seasonal outerwear and premium knitwear on orders over $120',
                'type' => 'percentage',
                'value' => 25.00,
                'min_spend' => 120.00,
                'max_discount' => 100.00,
                'usage_limit_total' => 300,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TAILORING30',
                'description' => '$30 instant VIP deduction on luxury tailoring orders over $150',
                'type' => 'fixed',
                'value' => 30.00,
                'min_spend' => 150.00,
                'max_discount' => null,
                'usage_limit_total' => 150,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'description' => 'Complimentary free expedited shipping coupon code on orders over $40',
                'type' => 'fixed',
                'value' => 15.00,
                'min_spend' => 40.00,
                'max_discount' => 15.00,
                'usage_limit_total' => 1000,
                'usage_limit_per_customer' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::updateOrCreate(['code' => $c['code']], $c);
        }
    }

    private function seedBlog(): void
    {
        $author = User::where('email', 'admin@wearday.com')->first() ?? User::first();
        if (!$author) {
            return;
        }

        $cats = [
            ['name' => 'Styling Guides', 'slug' => 'styling-guides'],
            ['name' => 'Wardrobe Foundations', 'slug' => 'wardrobe-foundations'],
            ['name' => 'Sustainable Living', 'slug' => 'sustainable-living'],
            ['name' => 'Fabric & Care', 'slug' => 'fabric-and-care'],
        ];

        $blogCats = [];
        foreach ($cats as $c) {
            $blogCats[$c['slug']] = BlogCategory::updateOrCreate(['slug' => $c['slug']], $c);
        }

        $tags = [
            ['name' => 'Capsule Wardrobe', 'slug' => 'capsule-wardrobe'],
            ['name' => 'Layering', 'slug' => 'layering'],
            ['name' => 'Everyday Style', 'slug' => 'everyday-style'],
            ['name' => 'Organic Textiles', 'slug' => 'organic-textiles'],
            ['name' => 'Care Guide', 'slug' => 'care-guide'],
            ['name' => 'Modern Tailoring', 'slug' => 'modern-tailoring'],
        ];

        $blogTags = [];
        foreach ($tags as $t) {
            $blogTags[$t['slug']] = BlogTag::updateOrCreate(['slug' => $t['slug']], $t);
        }

        $articles = [
            [
                'title' => '5 Casual Looks For Any Day',
                'slug' => '5-casual-looks-for-any-day',
                'excerpt' => 'Discover the 5 go-to outfit combinations designed for busy mornings, weekend coffee strolls, and everything in between.',
                'content' => '<p>Building a versatile capsule wardrobe does not require an endless closet. It begins with foundational garments engineered to pair harmoniously together.</p><p><strong>Look 1: The Monochromatic Neutral.</strong> Pair the Oatmeal Relaxed Fit Tee with British Khaki Easy Chinos and Clean White Leather Sneakers. The soft earthy tones deliver instant visual calm and refined elegance.</p><p><strong>Look 2: Weekend Layering.</strong> Layer our heavy-brushed Overshirt over a Forest Moss Essential Tee. Leave the buttons open for an easy drape.</p><p><strong>Look 3: Relaxed Indigo.</strong> Combine our Japanese selvedge denim trucker jacket with easy chinos and low-top leather kicks for an authentic weekend staple.</p><p><strong>Look 4: The Elevated Hoodie.</strong> A dense, structured French Terry hoodie paired with tailored trousers bridges the gap between home comfort and cafe sophistication.</p><p><strong>Look 5: Resort Ease.</strong> Wear an airy linen-blend button-down untucked over washed denim with the sleeves rolled twice.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['styling-guides']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'meta_title' => '5 Casual Looks For Any Day — WearDay Styling Guides',
                'meta_description' => '5 modern, effortless everyday outfit ideas for men and women.',
                'tags' => [$blogTags['capsule-wardrobe']->id, $blogTags['everyday-style']->id],
            ],
            [
                'title' => 'Layering 101 Made Simple',
                'slug' => 'layering-101-made-simple',
                'excerpt' => 'Master textures, weights, and proportions with our straightforward three-layer rule for unpredictable weather.',
                'content' => '<p>Layering is not just about staying warm; it is the most effective tool to introduce texture, depth, and personality to a minimalist outfit.</p><p><strong>The Base:</strong> Always start with a breathable organic cotton or modal base. A 180-220 GSM tee ensures natural temperature regulation against the skin.</p><p><strong>The Mid-Layer:</strong> Think utility overshirts, open-weave linen shirts, or fine-gauge knits. This layer provides thermal insulation while adding subtle tactile interest.</p><p><strong>The Outer Shell:</strong> A structured denim trucker or canvas chore jacket provides wind resistance and structural silhouette definition.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['styling-guides']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(10),
                'meta_title' => 'Layering 101 Made Simple — WearDay',
                'meta_description' => 'A guide to layering modern menswear and womenswear effortlessly.',
                'tags' => [$blogTags['layering']->id, $blogTags['capsule-wardrobe']->id],
            ],
            [
                'title' => 'The Complete Care Guide for Pure Cashmere & Merino Wool',
                'slug' => 'complete-care-guide-cashmere-merino-wool',
                'excerpt' => 'How washing techniques, cedar blocks, and gentle debobbling keep luxury knitwear pristine for decades.',
                'content' => '<p>Fine knitwear is an investment in timeless comfort. With proper care, Grade-A cashmere and extra-fine Merino wool garments can maintain their cloud-soft hand feel for a lifetime.</p><p><strong>Washing:</strong> Wool fibers are self-cleaning due to natural lanolin. Air out your garments between wears, and hand-wash only when necessary using cold water and specialized pH-neutral wool wash.</p><p><strong>Drying:</strong> Never wring or twist wet woolens. Roll the garment in a clean cotton towel to absorb excess water, then reshape and lay flat on a horizontal drying rack away from sunlight.</p><p><strong>Storage:</strong> Always store knits folded, never on hangers which will cause the shoulders to stretch. Protect against moths using natural red cedar blocks or lavender sachets.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['fabric-and-care']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(14),
                'meta_title' => 'Cashmere & Merino Wool Care Guide — WearDay',
                'meta_description' => 'Expert tips for washing, drying, and preserving luxury knitwear.',
                'tags' => [$blogTags['care-guide']->id, $blogTags['organic-textiles']->id],
            ],
            [
                'title' => 'Why French Flax Linen Is the Ultimate Warm-Weather Textile',
                'slug' => 'why-french-flax-linen-is-the-ultimate-summer-textile',
                'excerpt' => 'Explore the rich history, breathability, and natural thermo-regulating properties of Normandy linen fibers.',
                'content' => '<p>Few natural textiles command as much reverence during warm seasons as authentic French flax linen. Cultivated in the unique microclimate of Normandy, flax requires virtually no irrigation and zero synthetic pesticides.</p><p>The hollow structure of the flax fiber acts as a natural air conditioner, absorbing up to 20% of its weight in moisture before even feeling damp. Over time, friction and laundering break down pectin bonds, making the fabric noticeably softer with every passing year.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['sustainable-living']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(18),
                'meta_title' => 'Why French Flax Linen Is the Ultimate Summer Textile — WearDay',
                'meta_description' => 'The sustainability and performance advantages of French Normandy linen.',
                'tags' => [$blogTags['organic-textiles']->id, $blogTags['everyday-style']->id],
            ],
            [
                'title' => 'Modern Tailoring: Breaking Free From Rigid Office Rules',
                'slug' => 'modern-tailoring-breaking-free-office-rules',
                'excerpt' => 'How unconstructed shoulders, relaxed pleats, and pairing blazers with leather sneakers redefined formal dressing.',
                'content' => '<p>The modern wardrobe no longer abides by rigid, stiff corporate conventions. Today’s tailoring prioritizes ease of movement, soft unlined silhouettes, and versatile cross-context styling.</p><p>An unstructured wool blazer worn over a crisp heavyweight tee and relaxed pleated trousers delivers effortless polish without feeling constrained. Finish the look with clean white leather cupsole sneakers for the modern city aesthetic.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['wardrobe-foundations']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(22),
                'meta_title' => 'Modern Tailoring: Breaking Free From Rigid Office Rules — WearDay',
                'meta_description' => 'Unstructured blazers, relaxed pleats, and modern tailoring styling advice.',
                'tags' => [$blogTags['modern-tailoring']->id, $blogTags['capsule-wardrobe']->id],
            ],
            [
                'title' => 'The 10-Garment Capsule Wardrobe Blueprint',
                'slug' => 'the-10-garment-capsule-wardrobe-blueprint',
                'excerpt' => 'How ten thoughtfully selected garments can generate over 40 distinct, elevated outfits for every season.',
                'content' => '<p>Decision fatigue is real. By paring down your wardrobe to ten high-quality, interchangeable garments, you free up mental clarity while looking immaculately put together every morning.</p><p>The formula: 2 organic tees (Oatmeal & Black), 1 Oxford button-down, 1 heavy overshirt, 1 pair of selvedge denim, 1 pair of tailored chinos, 1 fine-gauge sweater, 1 timeless jacket, and 2 pairs of versatile footwear. Combine them across neutral earthy tones for effortless daily cohesion.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=800&auto=format&fit=crop&q=80',
                'category_id' => $blogCats['wardrobe-foundations']->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(26),
                'meta_title' => 'The 10-Garment Capsule Wardrobe Blueprint — WearDay',
                'meta_description' => 'Simplify your closet with our proven 10-piece capsule wardrobe formula.',
                'tags' => [$blogTags['capsule-wardrobe']->id, $blogTags['everyday-style']->id],
            ],
        ];

        foreach ($articles as $aData) {
            $postTags = $aData['tags'];
            unset($aData['tags']);

            $post = BlogPost::updateOrCreate(['slug' => $aData['slug']], $aData);
            $post->tags()->syncWithoutDetaching($postTags);
        }
    }

    private function seedReviews(): void
    {
        // Ensure test customer users exist to associate reviews with authentic customers
        $customerData = [
            ['name' => 'Sophia Montgomery', 'email' => 'sophia.m@example.com'],
            ['name' => 'Alexander Vance', 'email' => 'alexander.v@example.com'],
            ['name' => 'Elena Rostova', 'email' => 'elena.r@example.com'],
            ['name' => 'Marcus Chen', 'email' => 'marcus.c@example.com'],
            ['name' => 'Claire Beauchamp', 'email' => 'claire.b@example.com'],
            ['name' => 'David Sterling', 'email' => 'david.s@example.com'],
        ];

        $users = [];
        foreach ($customerData as $cd) {
            $users[] = User::firstOrCreate(['email' => $cd['email']], [
                'name' => $cd['name'],
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]);
        }

        $reviewsList = [
            [
                'product_slug' => 'classic-denim-trucker-jacket',
                'user_index' => 1,
                'rating' => 5,
                'title' => 'Unbelievable Japanese selvedge denim quality',
                'comment' => 'The weight and crisp hand feel of this Okayama denim is spectacular. The red-line selvedge on the inside placket is a fantastic detail. Breaks in beautifully after just two weeks of daily wear.',
                'admin_reply' => 'Thank you Alexander! The 14oz Toyoda loom denim develops incredible personal fades over time. Enjoy your jacket!',
            ],
            [
                'product_slug' => 'classic-denim-trucker-jacket',
                'user_index' => 3,
                'rating' => 5,
                'title' => 'Sturdy, structured, and timeless cut',
                'comment' => 'Fits true to size with just enough room to wear over a hoodie or sweater. Hardware has a heavy, satisfying feel.',
                'admin_reply' => 'We are delighted to hear you love the fit and custom oxidized brass hardware, Marcus!',
            ],
            [
                'product_slug' => 'oxford-cotton-button-down',
                'user_index' => 5,
                'rating' => 5,
                'title' => 'Best Oxford shirt in my closet',
                'comment' => 'The collar roll on this shirt is perfection. Mother-of-pearl buttons are gorgeous. Substantial fabric that does not feel flimsy or wrinkle immediately.',
                'admin_reply' => 'Thank you David! The 2-ply long-staple Egyptian cotton is selected specifically for that balance of weight and drape.',
            ],
            [
                'product_slug' => 'brushed-flannel-overshirt',
                'user_index' => 1,
                'rating' => 5,
                'title' => 'Heavyweight and so soft',
                'comment' => 'The brushed finish feels like cashmere on the skin. Love the olive sage colorway, pairs effortlessly with raw denim and boots.',
                'admin_reply' => 'Thanks for sharing Alexander! The peached twill finish is one of our favorite seasonal fabrics.',
            ],
            [
                'product_slug' => 'merino-wool-crewneck-sweater',
                'user_index' => 3,
                'rating' => 5,
                'title' => 'Zero itch and regulates temperature like magic',
                'comment' => 'I have sensitive skin and usually avoid wool, but this 19.5 micron Merino is ridiculously smooth. Lightweight enough to layer under a blazer.',
                'admin_reply' => 'Wonderful feedback Marcus! Ultra-fine Australian merino provides thermal comfort with absolute zero itch.',
            ],
            [
                'product_slug' => 'pleated-linen-trouser-shorts',
                'user_index' => 5,
                'rating' => 4,
                'title' => 'Elegant summer shorts that actually look tailored',
                'comment' => 'The forward pleats give them such a smart look. Elastic waist in the back makes them supremely comfortable for vacation dinners.',
                'admin_reply' => 'Thank you David! The hidden back elastic provides ease while maintaining the crisp front waistband.',
            ],
            [
                'product_slug' => 'structured-linen-trench-coat',
                'user_index' => 0,
                'rating' => 5,
                'title' => 'A show-stopping statement piece',
                'comment' => 'Every time I wear this trench out, people ask where it is from. The drape of the 280 GSM linen has weight and movement without being stuffy. Absolute perfection.',
                'admin_reply' => 'Thank you Sophia! The French Normandy linen gives the structured trench an unforgettable fluid silhouette.',
            ],
            [
                'product_slug' => 'structured-linen-trench-coat',
                'user_index' => 2,
                'rating' => 5,
                'title' => 'Exquisite tailoring and horn buttons',
                'comment' => 'The storm flaps and deep back pleat are tailored to couture standards. Worth every single penny.',
                'admin_reply' => 'We appreciate your detailed praise Elena! Enjoy turning heads this season.',
            ],
            [
                'product_slug' => 'satin-bias-cut-skirt',
                'user_index' => 4,
                'rating' => 5,
                'title' => 'Drapes like liquid silk',
                'comment' => 'The bias cut means it hugs the hips without clinging or wrinkling. The dusty rose color is ethereal. Wore it to a wedding with a cashmere knit.',
                'admin_reply' => 'So glad to hear that Claire! The Japanese crepe-back satin was engineered specifically for that fluid, non-static drape.',
            ],
            [
                'product_slug' => 'cashmere-turtleneck-sweater',
                'user_index' => 0,
                'rating' => 5,
                'title' => 'Pure luxury against the skin',
                'comment' => 'Cloud soft. The neck stays upright without feeling tight or choking. The oatmeal heather color is so calming and luxurious.',
                'admin_reply' => 'Thank you Sophia! Grade-A 34mm long-staple fibers ensure that cloud softness lasts year after year.',
            ],
            [
                'product_slug' => 'cashmere-turtleneck-sweater',
                'user_index' => 2,
                'rating' => 5,
                'title' => 'Keeps its shape with zero pilling',
                'comment' => 'After a month of consistent wear, practically zero pills. The 2-ply yarn is tight and resilient. Truly top-tier cashmere.',
                'admin_reply' => 'Delighted to hear Elena! Thank you for choosing WearDay for your knitwear collection.',
            ],
            [
                'product_slug' => 'tailored-linen-vest-waistcoat',
                'user_index' => 4,
                'rating' => 5,
                'title' => 'Chic, flattering, and completely opaque',
                'comment' => 'The cotton lining makes it 100% opaque so you can wear it with nothing underneath. The back cinch allows you to sculpt your waist perfectly.',
                'admin_reply' => 'Thank you Claire! The full interior lining and back cinch were key design priorities for versatile styling.',
            ],
            [
                'product_slug' => 'high-rise-wide-leg-jeans',
                'user_index' => 0,
                'rating' => 5,
                'title' => 'The holy grail of wide leg denim',
                'comment' => 'Holds everything in while creating an elongated silhouette. The 13oz organic denim feels substantial like genuine vintage jeans.',
                'admin_reply' => 'We are thrilled Sophia! The 100% organic rigid denim gets better with every single wear.',
            ],
            [
                'product_slug' => 'minimalist-crossbody-camera-bag',
                'user_index' => 2,
                'rating' => 5,
                'title' => 'Tuscan leather smells incredible',
                'comment' => 'The vegetable tan calfskin is already developing a gorgeous golden sheen. The solid brass zipper is smooth as butter. Holds phone, wallet, keys, and lip balm with room to spare.',
                'admin_reply' => 'Thank you Elena! Tuscan vegetable tanning creates an organic patina unique to its owner.',
            ],
            [
                'product_slug' => 'ribbed-cashmere-scarf',
                'user_index' => 1,
                'rating' => 5,
                'title' => 'Enormous, warm, and zero tassels',
                'comment' => 'At 200cm, you can wrap it twice and face any winter blizzard in comfort. The fringeless clean edges look so sleek with tailored coats.',
                'admin_reply' => 'Thank you Alexander! The 7-gauge dense knit was designed for maximum thermal warmth and modern clean lines.',
            ],
            [
                'product_slug' => 'clean-low-top-leather-sneakers',
                'user_index' => 3,
                'rating' => 5,
                'title' => 'Better than luxury designer sneakers costing 3x',
                'comment' => 'The Margom cupsole and buttery Italian calfskin are identical to $400+ luxury designer brands. No break-in blister period at all.',
                'admin_reply' => 'That is exactly why we created them Marcus! Direct-to-consumer artisanal craftsmanship without retail markups.',
            ],
            [
                'product_slug' => 'clean-low-top-leather-sneakers',
                'user_index' => 5,
                'rating' => 5,
                'title' => 'Crisp, versatile, and ridiculously comfortable',
                'comment' => 'I wear these with both suits and selvedge jeans. The memory foam insole makes 10,000 steps around the city effortless.',
                'admin_reply' => 'Thank you David! The memory foam footbed and Nappa calfskin lining keep steps cushioned all day.',
            ],
            [
                'product_slug' => 'suede-chelsea-boots',
                'user_index' => 1,
                'rating' => 5,
                'title' => 'Goodyear welted and genuinely water-resistant',
                'comment' => 'Got caught in sudden heavy rain and the water just beaded right off the calf suede. The British Dainite sole provides incredible grip on wet pavement.',
                'admin_reply' => 'Thank you Alexander! Dainite studded rubber and treated suede give you confidence in all weather conditions.',
            ],
            [
                'product_slug' => 'relaxed-fit-tee',
                'user_index' => 3,
                'rating' => 5,
                'title' => 'Heavyweight tee with the perfect drop shoulder',
                'comment' => 'At 220 GSM this t-shirt has great structure. The bound collar does not sag or bacon after multiple washes. Ordering 3 more.',
                'admin_reply' => 'Thank you Marcus! Our 220 GSM combed organic jersey is preshrunk to guarantee collar and shape retention.',
            ],
            [
                'product_slug' => 'relaxed-fit-tee',
                'user_index' => 5,
                'rating' => 5,
                'title' => 'Best casual foundation tee',
                'comment' => 'The Oatmeal Heather shade is gorgeous in person. Substantial weight and very soft drape.',
                'admin_reply' => 'We are so glad you love the Oatmeal shade David! It is a signature core color.',
            ],
        ];

        foreach ($reviewsList as $rev) {
            $product = Product::where('slug', $rev['product_slug'])->first();
            if (!$product) {
                continue;
            }

            $user = $users[$rev['user_index']] ?? $users[0];

            $review = ProductReview::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                ],
                [
                    'rating' => $rev['rating'],
                    'title' => $rev['title'],
                    'comment' => $rev['comment'],
                    'is_verified_purchase' => true,
                    'is_approved' => true,
                    'admin_reply' => $rev['admin_reply'],
                ]
            );

            // Automatically recalculate product's rating and review count
            $review->updateProductStats();
        }
    }
}
