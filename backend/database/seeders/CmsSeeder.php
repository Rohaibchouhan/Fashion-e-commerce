<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\TrustBadge;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // Banners
        Banner::firstOrCreate(['title' => 'The Spring/Summer Edit'], [
            'subtitle' => 'Architectural silhouettes and featherweight flax linens designed for balmy days.',
            'image_desktop' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&auto=format&fit=crop&q=80',
            'image_mobile' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
            'button_text' => 'Explore the Collection',
            'button_url' => '/catalog?category=all',
            'position' => 'hero_main',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Banner::firstOrCreate(['title' => 'Tailored Essentials'], [
            'subtitle' => 'Modern luxury fundamentals engineered with organic cotton and Japanese indigo.',
            'image_desktop' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=1600&auto=format&fit=crop&q=80',
            'image_mobile' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?w=800&auto=format&fit=crop&q=80',
            'button_text' => 'Shop Menswear',
            'button_url' => '/catalog?gender=men',
            'position' => 'hero_main',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Trust Badges
        $badges = [
            [
                'title' => 'Complimentary Delivery',
                'description' => 'Free standard ground shipping on all orders over $50 across the country.',
                'icon' => 'Truck',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => '30-Day Effortless Returns',
                'description' => 'Try at home with complete peace of mind. Easy returns and instant exchanges.',
                'icon' => 'RotateCcw',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Ethical Craftsmanship',
                'description' => '100% GOTS certified organic fibers and transparent, fair wage atelier partners.',
                'icon' => 'ShieldCheck',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Secure Checkout',
                'description' => 'Bank-grade 256-bit SSL encryption and tokenized payment security.',
                'icon' => 'Lock',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($badges as $b) {
            TrustBadge::firstOrCreate(['title' => $b['title']], $b);
        }

        // FAQs
        $faqs = [
            [
                'question' => 'How does WearDay sizing work?',
                'answer' => 'Our garments are tailored true to modern American/European sizing. For boxy cuts (like our Relaxed Fit Tee), choose your typical size for the intended drape, or size down for a slimmer silhouette. You will find an exact measurement chart on every product detail page.',
                'category' => 'sizing',
                'sort_order' => 1,
            ],
            [
                'question' => 'What is your return policy?',
                'answer' => 'We proudly offer a 30-day return window from the day your package is delivered. Items must be unworn, unwashed, with original brand tags attached. Return shipping labels are generated automatically through your online account portal.',
                'category' => 'returns',
                'sort_order' => 2,
            ],
            [
                'question' => 'How long does shipping take?',
                'answer' => 'Orders placed by 2 PM EST ship same day. Standard Ground shipping typically arrives within 3-5 business days. FedEx Express courier arrives in 1-2 business days.',
                'category' => 'shipping',
                'sort_order' => 3,
            ],
            [
                'question' => 'Do you allow guest checkout?',
                'answer' => 'To guarantee flawless order tracking, shipment tracking, seamless returns, and address history, we automatically create a secure customer account for new shoppers upon checkout with a password setup link sent to your email.',
                'category' => 'orders',
                'sort_order' => 4,
            ],
        ];

        foreach ($faqs as $f) {
            Faq::firstOrCreate(['question' => $f['question']], $f);
        }

        // Static Pages
        Page::firstOrCreate(['slug' => 'about-us'], [
            'title' => 'About WearDay',
            'excerpt' => 'At WearDay, we build enduring modern clothing designed to transcend fleeting trends.',
            'content' => '<h1>Crafted for the Modern Rhythm</h1><p>Founded on the belief that everyday clothing should marry impeccable cut with uncompromising textile integrity, WearDay creates wardrobe foundations for men and women.</p>',
            'is_published' => true,
            'meta_title' => 'About WearDay — Mindfully Crafted Modern Wardrobe',
            'meta_description' => 'Learn about WearDay craftsmanship, ethical organic materials, and design philosophy.',
        ]);

        Page::firstOrCreate(['slug' => 'shipping-returns'], [
            'title' => 'Shipping & Returns Policy',
            'excerpt' => 'All you need to know about our swift delivery times and effortless return process.',
            'content' => '<h1>Shipping & Returns</h1><p>Enjoy free standard shipping on all orders over $50. Returns are accepted within 30 days of delivery.</p>',
            'is_published' => true,
            'meta_title' => 'WearDay — Shipping, Delivery & Return Guidelines',
            'meta_description' => 'Details on domestic US delivery options, carrier tracking, and 30-day return procedures.',
        ]);
    }
}

