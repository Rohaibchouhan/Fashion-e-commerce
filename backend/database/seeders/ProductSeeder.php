<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::first();
        $menCategory = Category::where('slug', 'men-t-shirts')->first() ?? Category::first();
        $womenCategory = Category::where('slug', 'women-tops')->first() ?? Category::first();
        $accessoriesCategory = Category::where('slug', 'accessories')->first() ?? Category::first();
        $menJeansCategory = Category::where('slug', 'men-jeans')->first() ?? Category::first();
        $menJacketsCategory = Category::where('slug', 'men-jackets')->first() ?? Category::first();
        $menHoodiesCategory = Category::where('slug', 'men-hoodies')->first() ?? Category::first();

        $productsData = [
            [
                'slug' => 'relaxed-fit-tee',
                'name' => 'Relaxed Fit Tee',
                'sku' => 'RFT-100',
                'subtitle' => 'Heavyweight 220 GSM organic combed cotton',
                'description' => 'An everyday wardrobe foundation cut with a modern relaxed drop-shoulder silhouette. Preshrunk for lasting shape and finished with a comfortable bound rib collar.',
                'category_id' => $menCategory->id,
                'gender' => 'men',
                'regular_price' => 24.99,
                'sale_price' => 19.99,
                'cost_price' => 8.50,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% GOTS Certified Organic Cotton',
                'fit' => 'Relaxed Boxy Fit. Take your regular size for an easy drape, or size down for tailored fit.',
                'details' => [
                    '220 GSM heavyweight organic cotton jersey',
                    'Relaxed boxy cut with subtle drop shoulder',
                    'Reinforced coverstitched collar and hems',
                    'Pre-washed to eliminate post-wash shrinkage',
                ],
                'care' => [
                    'Machine wash cold with like colors',
                    'Tumble dry low or air dry in shade',
                    'Warm iron if needed; do not dry clean',
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'RFT-OAT-S', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'S', 'price' => 19.99, 'compare_at_price' => 24.99, 'stock' => 18, 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80'],
                    ['sku' => 'RFT-OAT-M', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'M', 'price' => 19.99, 'compare_at_price' => 24.99, 'stock' => 32, 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80'],
                    ['sku' => 'RFT-OAT-L', 'color' => 'Oatmeal Heather', 'color_hex' => '#e5ded4', 'size' => 'L', 'price' => 19.99, 'compare_at_price' => 24.99, 'stock' => 24, 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80'],
                    ['sku' => 'RFT-OLI-M', 'color' => 'Olive Sage', 'color_hex' => '#5a6855', 'size' => 'M', 'price' => 19.99, 'compare_at_price' => 24.99, 'stock' => 15, 'image' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80'],
                    ['sku' => 'RFT-BLK-L', 'color' => 'Washed Black', 'color_hex' => '#222222', 'size' => 'L', 'price' => 19.99, 'compare_at_price' => 24.99, 'stock' => 20, 'image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=800&auto=format&fit=crop&q=80'],
                ],
            ],
            [
                'slug' => 'linen-blend-shirt',
                'name' => 'Linen Blend Shirt',
                'sku' => 'LBS-200',
                'subtitle' => 'Airy breathable linen with soft French cotton blend',
                'description' => 'Designed for balmy days and effortless layering. Features natural slub texture, mother-of-pearl buttons, and a clean camp collar that lays smooth against the collarbone.',
                'category_id' => $womenCategory->id,
                'gender' => 'women',
                'regular_price' => 42.00,
                'sale_price' => 34.99,
                'cost_price' => 14.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '55% Flax Linen, 45% Organic Cotton',
                'fit' => 'Easy casual drape. Slightly oversized for laid-back sophistication.',
                'details' => [
                    '55% European flax linen, 45% organic combed cotton',
                    'Breathable, lightweight, natural slub weave',
                    'Relaxed resort silhouette with subtle curved hem',
                    'Corozo nut eco-friendly buttons',
                ],
                'care' => [
                    'Delicate machine wash cold',
                    'Line dry in shade',
                    'Steam or warm iron for crisp finish',
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1598554747436-c9293d6a588f?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'LBS-SKY-S', 'color' => 'Sky Striped', 'color_hex' => '#9bbcd8', 'size' => 'S', 'price' => 34.99, 'compare_at_price' => 42.00, 'stock' => 19],
                    ['sku' => 'LBS-SKY-M', 'color' => 'Sky Striped', 'color_hex' => '#9bbcd8', 'size' => 'M', 'price' => 34.99, 'compare_at_price' => 42.00, 'stock' => 22],
                    ['sku' => 'LBS-CHK-M', 'color' => 'Clean Chalk', 'color_hex' => '#f8f7f2', 'size' => 'M', 'price' => 34.99, 'compare_at_price' => 42.00, 'stock' => 15],
                ],
            ],
            [
                'slug' => 'easy-chino-pants',
                'name' => 'Easy Chino Pants',
                'sku' => 'ECP-300',
                'subtitle' => 'Tapered stretch twill with elasticated back waist',
                'description' => 'The comfort of casual loungewear blended into a tailored pant. Features a flat-front tailored waist with hidden elastic in the back, deep slash pockets, and a clean ankle break.',
                'category_id' => $menJeansCategory->id,
                'gender' => 'men',
                'regular_price' => 49.99,
                'sale_price' => 39.99,
                'cost_price' => 16.50,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => false,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '98% Cotton, 2% Elastane',
                'fit' => 'Regular fit through thigh tapering gently down to the ankle.',
                'details' => [
                    '98% cotton twill with 2% elastane flexible stretch',
                    'Concealed interior drawstring with belt loops',
                    'YKK zip fly with horn-style button closure',
                    'Twin welt back pockets with button tab',
                ],
                'care' => ['Machine wash warm inside out', 'Tumble dry low', 'Medium iron'],
                'images' => [
                    'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'ECP-OLI-32', 'color' => 'Olive Branch', 'color_hex' => '#565c47', 'size' => '32', 'price' => 39.99, 'compare_at_price' => 49.99, 'stock' => 28],
                    ['sku' => 'ECP-KHA-32', 'color' => 'British Khaki', 'color_hex' => '#c4b18c', 'size' => '32', 'price' => 39.99, 'compare_at_price' => 49.99, 'stock' => 25],
                ],
            ],
            [
                'slug' => 'everyday-hoodie',
                'name' => 'Everyday Hoodie',
                'sku' => 'EDH-400',
                'subtitle' => 'Brushed French terry comfort hoodie',
                'description' => 'Crafted from 400 GSM luxury French terry fleece with a dense, structured feel. Features double-layered hood without drawstring clutter, ribbed side gussets, and kangaroo pocket.',
                'category_id' => $menHoodiesCategory->id,
                'gender' => 'men',
                'regular_price' => 55.00,
                'sale_price' => 44.99,
                'cost_price' => 19.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Organic Cotton Terry',
                'fit' => 'Relaxed unisex fit with structured shoulders.',
                'details' => [
                    '400 GSM heavyweight brushed French terry',
                    'Seamless double-layer hood construction',
                    'Ribbed side panels for unrestricted freedom of movement',
                ],
                'care' => ['Machine wash cold', 'Lay flat to dry', 'Do not bleach'],
                'images' => [
                    'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'EDH-SAG-M', 'color' => 'Sage Green', 'color_hex' => '#6b7a66', 'size' => 'M', 'price' => 44.99, 'compare_at_price' => 55.00, 'stock' => 40],
                    ['sku' => 'EDH-TAU-L', 'color' => 'Warm Taupe', 'color_hex' => '#b5a397', 'size' => 'L', 'price' => 44.99, 'compare_at_price' => 55.00, 'stock' => 35],
                ],
            ],
            [
                'slug' => 'denim-jacket',
                'name' => 'Denim Jacket',
                'sku' => 'DNJ-500',
                'subtitle' => 'Authentic 13oz vintage washed selvedge-inspired denim',
                'description' => 'A time-honored staple tailored with modern clean lines. Softened with a stone enzyme rinse for that lived-in comfort from the very first wear.',
                'category_id' => $menJacketsCategory->id,
                'gender' => 'women',
                'regular_price' => 79.99,
                'sale_price' => 59.99,
                'cost_price' => 26.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => false,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Rigid Cotton Denim',
                'fit' => 'Slightly cropped classic trucker jacket silhouette.',
                'details' => [
                    '13oz ring-spun cotton denim',
                    'Antique brass shank hardware',
                    'Dual chest flap pockets plus interior dump pockets',
                ],
                'care' => ['Wash infrequently inside out', 'Cold water gentle cycle', 'Hang dry'],
                'images' => [
                    'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'DNJ-VNT-S', 'color' => 'Mid Vintage Blue', 'color_hex' => '#4a6d8c', 'size' => 'S', 'price' => 59.99, 'compare_at_price' => 79.99, 'stock' => 14],
                    ['sku' => 'DNJ-VNT-M', 'color' => 'Mid Vintage Blue', 'color_hex' => '#4a6d8c', 'size' => 'M', 'price' => 59.99, 'compare_at_price' => 79.99, 'stock' => 20],
                ],
            ],
            [
                'slug' => 'essential-tee',
                'name' => 'Essential Tee',
                'sku' => 'EST-600',
                'subtitle' => 'Ultra-soft daily crew neck tee',
                'description' => 'The tee you will reach for every morning. Featherlight yet durable, tailored with precision seams and tagless neck comfort.',
                'category_id' => $menCategory->id,
                'gender' => 'men',
                'regular_price' => 22.00,
                'sale_price' => 19.99,
                'cost_price' => 7.00,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => false,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Combed Cotton',
                'fit' => 'Classic regular fit. True to size.',
                'details' => ['180 GSM combed long-staple cotton', 'Fine ribbed crewneck collar'],
                'care' => ['Machine wash cold', 'Tumble dry low'],
                'images' => [
                    'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'EST-FOR-M', 'color' => 'Forest Moss', 'color_hex' => '#4b5d44', 'size' => 'M', 'price' => 19.99, 'compare_at_price' => 22.00, 'stock' => 45],
                    ['sku' => 'EST-WHT-M', 'color' => 'Crisp White', 'color_hex' => '#ffffff', 'size' => 'M', 'price' => 19.99, 'compare_at_price' => 22.00, 'stock' => 50],
                ],
            ],
            [
                'slug' => 'overshirt',
                'name' => 'Overshirt',
                'sku' => 'OVS-700',
                'subtitle' => 'Heavyweight utility overshirt jacket',
                'description' => 'The ideal middle ground between a sturdy shirt and a lightweight jacket. Cut from heavy brushed cotton with utility patch pockets.',
                'category_id' => $menJacketsCategory->id,
                'gender' => 'men',
                'regular_price' => 65.00,
                'sale_price' => 49.99,
                'cost_price' => 21.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Cotton Twill',
                'fit' => 'Relaxed overshirt fit. Sized to wear easily over tees and light sweaters.',
                'details' => ['280 GSM brushed twill canvas', 'Square bottom hem for wearing untucked'],
                'care' => ['Machine wash cold gentle', 'Hang dry'],
                'images' => [
                    'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'OVS-ECR-M', 'color' => 'Natural Ecru', 'color_hex' => '#ded9cb', 'size' => 'M', 'price' => 49.99, 'compare_at_price' => 65.00, 'stock' => 25],
                    ['sku' => 'OVS-ECR-L', 'color' => 'Natural Ecru', 'color_hex' => '#ded9cb', 'size' => 'L', 'price' => 49.99, 'compare_at_price' => 65.00, 'stock' => 22],
                ],
            ],
            [
                'slug' => 'clean-sneakers',
                'name' => 'Clean Sneakers',
                'sku' => 'CSN-800',
                'subtitle' => 'Handcrafted white leather low-top sneakers',
                'description' => 'Stripped of superfluous accents to highlight immaculate lines and full-grain Italian leather. Includes cushioned memory-foam footbed and durable Margom-style rubber soles.',
                'category_id' => $accessoriesCategory->id,
                'gender' => 'unisex',
                'regular_price' => 89.99,
                'sale_price' => 69.99,
                'cost_price' => 32.00,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Genuine Leather Upper, Rubber Outsole',
                'fit' => 'Runs true to size. If between sizes, choose the larger size.',
                'details' => ['Supple full-grain calf leather upper', 'Vulcanized stitched rubber cupsole'],
                'care' => ['Wipe clean with damp cloth', 'Apply leather balm monthly'],
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CSN-WHT-42', 'color' => 'All White', 'color_hex' => '#fcfcfc', 'size' => '42', 'price' => 69.99, 'compare_at_price' => 89.99, 'stock' => 25],
                    ['sku' => 'CSN-WHT-43', 'color' => 'All White', 'color_hex' => '#fcfcfc', 'size' => '43', 'price' => 69.99, 'compare_at_price' => 89.99, 'stock' => 20],
                ],
            ],
            [
                'slug' => 'canvas-tote-bag',
                'name' => 'Canvas Tote Bag',
                'sku' => 'CTB-900',
                'subtitle' => 'Heavyweight 16oz raw duck canvas market tote',
                'description' => 'Built to shoulder daily essentials, groceries, or tech gear. Reinforced cross-stitched handles with internal zipped key pocket.',
                'category_id' => $accessoriesCategory->id,
                'gender' => 'unisex',
                'regular_price' => 30.00,
                'sale_price' => 24.99,
                'cost_price' => 9.50,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => false,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Unbleached Cotton Canvas',
                'fit' => 'One Size (16 x 14 x 5 inches)',
                'details' => ['16oz unbleached heavy cotton canvas', 'Spacious main compartment (24L capacity)'],
                'care' => ['Spot clean with mild soap and water'],
                'images' => [
                    'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CTB-NAT-OS', 'color' => 'Natural Canvas', 'color_hex' => '#ece6d8', 'size' => 'One Size', 'price' => 24.99, 'compare_at_price' => 30.00, 'stock' => 50],
                ],
            ],
            [
                'slug' => 'cotton-twill-cap',
                'name' => 'Cotton Twill Cap',
                'sku' => 'CAP-1000',
                'subtitle' => 'Unstructured 6-panel dad cap',
                'description' => 'Low-profile baseball cap with curved brim and brass slide buckle closure. Clean, unbranded aesthetic that elevates relaxed weekend wear.',
                'category_id' => $accessoriesCategory->id,
                'gender' => 'unisex',
                'regular_price' => 18.00,
                'sale_price' => 14.99,
                'cost_price' => 5.20,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'is_trending' => false,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Cotton Twill',
                'fit' => 'Adjustable back strap fits head circumferences 54-61cm.',
                'details' => ['100% brushed cotton twill', 'Brass buckle adjustable strapback'],
                'care' => ['Hand wash in cold water'],
                'images' => [
                    'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'CAP-OLI-OS', 'color' => 'Olive Moss', 'color_hex' => '#565c47', 'size' => 'One Size', 'price' => 14.99, 'compare_at_price' => 18.00, 'stock' => 35],
                ],
            ],
            [
                'slug' => 'slim-fit-jeans',
                'name' => 'Slim Fit Jeans',
                'sku' => 'SFJ-1100',
                'subtitle' => 'Authentic comfort stretch indigo denim',
                'description' => 'Clean modern slim cut with a mid rise and tailored leg opening. Made from premium Japanese-woven indigo cotton with just 1% elastane for seamless all-day flexibility.',
                'category_id' => $menJeansCategory->id,
                'gender' => 'men',
                'regular_price' => 65.00,
                'sale_price' => 49.99,
                'cost_price' => 20.00,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => true,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '99% Cotton, 1% Elastane',
                'fit' => 'Slim tailored leg from knee to ankle.',
                'details' => ['12.5oz premium comfort-stretch denim', 'Chain-stitched hem'],
                'care' => ['Machine wash cold inside out', 'Air dry'],
                'images' => [
                    'https://images.unsplash.com/photo-1542272604-780c96856592?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'SFJ-MBL-32', 'color' => 'Washed Medium Blue', 'color_hex' => '#3b5874', 'size' => '32', 'price' => 49.99, 'compare_at_price' => 65.00, 'stock' => 30],
                ],
            ],
            [
                'slug' => 'basic-crew-neck-tee',
                'name' => 'Basic Crew Neck Tee',
                'sku' => 'BCN-1200',
                'subtitle' => 'Everyday luxury essential tee in deep navy',
                'description' => 'A tailored essential cut from single-origin combed organic jersey. Breathable, durable, and naturally moisture-regulating.',
                'category_id' => $menCategory->id,
                'gender' => 'men',
                'regular_price' => 22.00,
                'sale_price' => 17.99,
                'cost_price' => 6.50,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'is_trending' => false,
                'rating' => 0.00,
                'review_count' => 0,
                'fabric' => '100% Organic Ring-Spun Cotton',
                'fit' => 'Standard regular fit.',
                'details' => ['190 GSM ring-spun cotton', 'Ribbed collar that holds shape'],
                'care' => ['Machine wash cold', 'Tumble dry low'],
                'images' => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80',
                ],
                'variants' => [
                    ['sku' => 'BCN-NAV-M', 'color' => 'Deep Navy', 'color_hex' => '#1c2536', 'size' => 'M', 'price' => 17.99, 'compare_at_price' => 22.00, 'stock' => 38],
                ],
            ],
        ];

        foreach ($productsData as $data) {
            $variants = $data['variants'];
            $images = $data['images'];
            unset($data['variants'], $data['images']);

            $data['brand_id'] = $brand?->id;
            $data['type'] = 'variable';
            $data['is_active'] = true;

            $product = Product::firstOrCreate(['slug' => $data['slug']], $data);

            // Images
            foreach ($images as $index => $imageUrl) {
                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'url' => $imageUrl],
                    [
                        'alt' => $product->name,
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
                $varData['cost_price'] = $data['cost_price'];
                $varData['is_active'] = true;
                $varData['stock'] = $stock;

                $variant = ProductVariant::firstOrCreate(['sku' => $varData['sku']], $varData);

                // Inventory Record
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $product->id, 'variant_id' => $variant->id],
                    [
                        'quantity' => $stock,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 5,
                    ]
                );

                // Initial Inventory Movement
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
                        'notes' => 'Initial inventory load for fashion catalog launch',
                    ]
                );
            }
        }
    }
}

