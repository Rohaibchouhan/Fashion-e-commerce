<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'admin@wearday.com')->first() ?? User::first();
        if (!$author) {
            return;
        }

        $stylingCat = BlogCategory::firstOrCreate(
            ['slug' => 'styling-guides'],
            ['name' => 'Styling Guides']
        );

        $wardrobeCat = BlogCategory::firstOrCreate(
            ['slug' => 'wardrobe-foundations'],
            ['name' => 'Wardrobe Foundations']
        );

        $tagCapsule = BlogTag::firstOrCreate(['slug' => 'capsule-wardrobe'], ['name' => 'Capsule Wardrobe']);
        $tagLayering = BlogTag::firstOrCreate(['slug' => 'layering'], ['name' => 'Layering']);
        $tagEveryday = BlogTag::firstOrCreate(['slug' => 'everyday-style'], ['name' => 'Everyday Style']);

        $posts = [
            [
                'title' => '5 Casual Looks For Any Day',
                'slug' => '5-casual-looks-for-any-day',
                'excerpt' => 'Discover the 5 go-to outfit combinations designed for busy mornings, weekend coffee strolls, and everything in between.',
                'content' => '<p>Building a versatile capsule wardrobe does not require an endless closet. It begins with foundational garments engineered to pair harmoniously together.</p><p><strong>Look 1: The Monochromatic Neutral.</strong> Pair the Oatmeal Relaxed Fit Tee with British Khaki Easy Chinos and Clean White Leather Sneakers. The soft earthy tones deliver instant visual calm and refined elegance.</p><p><strong>Look 2: Weekend Layering.</strong> Layer our heavy-brushed Overshirt over a Forest Moss Essential Tee. Leave the buttons open for an easy drape.</p><p><strong>Look 3: Relaxed Indigo.</strong> Combine a washed denim jacket with easy chinos and low-top leather kicks for an authentic weekend staple.</p><p><strong>Look 4: The Elevated Hoodie.</strong> A dense, structured French Terry hoodie paired with tailored trousers bridges the gap between home comfort and cafe sophistication.</p><p><strong>Look 5: Resort Ease.</strong> Wear an airy linen-blend button-down untucked over washed denim with the sleeves rolled twice.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&auto=format&fit=crop&q=80',
                'category_id' => $stylingCat->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(7),
                'meta_title' => '5 Casual Looks For Any Day — WearDay Styling Guides',
                'meta_description' => '5 modern, effortless everyday outfit ideas for men and women.',
                'tags' => [$tagCapsule->id, $tagEveryday->id],
            ],
            [
                'title' => 'Layering 101 Made Simple',
                'slug' => 'layering-101-made-simple',
                'excerpt' => 'Master textures, weights, and proportions with our straightforward three-layer rule for unpredictable weather.',
                'content' => '<p>Layering is not just about staying warm; it is the most effective tool to introduce texture, depth, and personality to a minimalist outfit.</p><p><strong>The Base:</strong> Always start with a breathable organic cotton or modal base. A 180-220 GSM tee ensures natural temperature regulation against the skin.</p><p><strong>The Mid-Layer:</strong> Think utility overshirts, open-weave linen shirts, or fine-gauge knits. This layer provides thermal insulation while adding subtle tactile interest.</p><p><strong>The Outer Shell:</strong> A structured denim trucker or canvas chore jacket provides wind resistance and structural silhouette definition.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
                'category_id' => $stylingCat->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(12),
                'meta_title' => 'Layering 101 Made Simple — WearDay',
                'meta_description' => 'A guide to layering modern menswear and womenswear effortlessly.',
                'tags' => [$tagLayering->id, $tagCapsule->id],
            ],
            [
                'title' => 'From Work To Weekend',
                'slug' => 'from-work-to-weekend',
                'excerpt' => 'How versatile tailoring, subtle palettes, and clean footwear make desk-to-dinner transitions effortless.',
                'content' => '<p>Modern work environments require clothes that transition effortlessly between professional settings and personal evenings.</p><p>Start with sharp, tapered trousers crafted with built-in stretch. Pair them with a clean crisp tailored tee during daylight hours, and slip into an Italian unstructured blazer for evening reservations.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                'category_id' => $wardrobeCat->id,
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDays(18),
                'meta_title' => 'From Work To Weekend — WearDay Wardrobe',
                'meta_description' => 'Desk to dinner styling principles for modern urban living.',
                'tags' => [$tagEveryday->id],
            ],
        ];

        foreach ($posts as $postData) {
            $tags = $postData['tags'];
            unset($postData['tags']);

            $post = BlogPost::firstOrCreate(['slug' => $postData['slug']], $postData);
            $post->tags()->syncWithoutDetaching($tags);
        }
    }
}
