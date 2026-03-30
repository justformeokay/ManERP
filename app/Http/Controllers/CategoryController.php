<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('parent_id', null)
            ->with('children')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::orderBy('name')->get();
        return view('inventory.categories.form', [
            'category' => new Category(),
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'slug' => 'nullable|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = str($validated['name'])->slug();
        }

        Category::create($validated);

        return redirect()->route('inventory.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();
        
        return view('inventory.categories.form', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = str($validated['name'])->slug();
        }

        $category->update($validated);

        return redirect()->route('inventory.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products. Please reassign products first.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories. Please delete subcategories first.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')->with('success', 'Category deleted successfully.');
    }
}
