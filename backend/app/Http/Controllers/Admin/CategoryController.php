<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['parent', 'children'])->orderBy('sort_order')->paginate(20);
        $parentCategories = Category::whereNull('parent_id')->get();

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'gender' => 'required|in:men,women,unisex,all',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|url',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        Category::create($validated);

        return back()->with('success', "Category '{$validated['name']}' created.");
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        return back()->with('success', "Category '{$name}' removed.");
    }
}

