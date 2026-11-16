<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->paginate(15);
        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name',
            'description' => 'nullable|string|max:500',
            'website' => 'nullable|url',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        Brand::create($validated);

        return back()->with('success', "Brand '{$validated['name']}' added.");
    }

    public function destroy(Brand $brand)
    {
        $name = $brand->name;
        $brand->delete();

        return back()->with('success', "Brand '{$name}' deleted.");
    }
}

