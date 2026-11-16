<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\TrustBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function banners(): JsonResponse
    {
        $banners = Banner::active()->get();

        return response()->json([
            'success' => true,
            'data' => $banners->map(fn ($b) => [
                'id' => (string) $b->id,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'imageDesktop' => $b->image_desktop,
                'imageMobile' => $b->image_mobile ?? $b->image_desktop,
                'buttonText' => $b->button_text,
                'buttonUrl' => $b->button_url,
                'position' => $b->position,
            ]),
        ]);
    }

    public function homepageSections(): JsonResponse
    {
        $sections = HomepageSection::active()->get();

        return response()->json([
            'success' => true,
            'data' => $sections->keyBy('section_key'),
        ]);
    }

    public function trustBadges(): JsonResponse
    {
        $badges = TrustBadge::active()->get();

        return response()->json([
            'success' => true,
            'data' => $badges->map(fn ($b) => [
                'id' => (string) $b->id,
                'title' => $b->title,
                'description' => $b->description,
                'icon' => $b->icon,
            ]),
        ]);
    }

    public function faqs(Request $request): JsonResponse
    {
        $query = Faq::active();
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'excerpt' => $page->excerpt,
                'metaTitle' => $page->meta_title,
                'metaDescription' => $page->meta_description,
            ],
        ]);
    }

    public function blogPosts(): JsonResponse
    {
        $posts = BlogPost::published()
            ->with(['category', 'author', 'tags'])
            ->latest('published_at')
            ->paginate(9);

        return response()->json([
            'success' => true,
            'data' => $posts->map(fn ($p) => [
                'id' => (string) $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'featuredImage' => $p->featured_image,
                'category' => $p->category?->name,
                'author' => $p->author->name,
                'publishedAt' => $p->published_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function blogPost(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->with(['category', 'author', 'tags'])
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Blog article not found.'], 404);
        }

        $post->increment('view_count');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featuredImage' => $post->featured_image,
                'category' => $post->category?->name,
                'author' => $post->author->name,
                'tags' => $post->tags->pluck('name')->toArray(),
                'publishedAt' => $post->published_at?->toISOString(),
                'viewCount' => $post->view_count,
            ],
        ]);
    }
}

