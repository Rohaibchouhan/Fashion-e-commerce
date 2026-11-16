<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\TrustBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    public function banners()
    {
        $banners = Banner::orderBy('sort_order')->get();
        $sections = HomepageSection::orderBy('sort_order')->get();
        $trustBadges = TrustBadge::orderBy('sort_order')->get();

        return view('admin.cms.banners', compact('banners', 'sections', 'trustBadges'));
    }

    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'image_desktop' => 'required|url',
            'button_text' => 'nullable|string|max:50',
            'button_url' => 'nullable|string|max:255',
            'position' => 'required|string',
        ]);

        Banner::create($validated);
        return back()->with('success', 'Banner created.');
    }

    public function pages()
    {
        $pages = Page::latest()->paginate(10);
        $faqs = Faq::orderBy('sort_order')->get();

        return view('admin.cms.pages', compact('pages', 'faqs'));
    }

    public function storePage(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'excerpt' => 'nullable|string|max:300',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:300',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        Page::create($validated);

        return back()->with('success', 'Page created.');
    }

    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string',
        ]);

        Faq::create($validated);
        return back()->with('success', 'FAQ entry added.');
    }

    public function blog()
    {
        $posts = BlogPost::with(['category', 'author'])->latest()->paginate(15);
        return view('admin.cms.blog', compact('posts'));
    }
}

