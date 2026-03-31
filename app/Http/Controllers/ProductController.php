<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use Auditable;

    protected string $model = 'products';
    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category', 'inventoryStocks')
            ->search($request->input('search'))
            ->when($request->input('category_id'), fn($q, $c) => $q->where('category_id', $c))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->when($request->has('active'), fn($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('inventory.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('inventory.products.form', [
            'product'    => new Product(['type' => 'finished_good', 'unit' => 'pcs', 'is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());
        $this->logCreate($product);

        return redirect()->route('inventory.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('inventory.products.form', [
            'product'    => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $oldData = $product->getOriginal();
        $product->update($request->validated());
        $this->logUpdate($product, $oldData);

        return redirect()->route('inventory.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->logDelete($product);
        $product->delete();

        return redirect()->route('inventory.products.index')->with('success', 'Product deleted successfully.');
    }
}
