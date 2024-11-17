<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $q = Product::query();

        // Filters
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $products = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.products.index', [
            'page_title' => wncms_model_word('product', 'management'),
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(Product $product = null)
    {
        $product ??= new Product;

        return view('wncms::backend.products.create', [
            'page_title' => wncms_model_word('product', 'create'),
            'product' => $product,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:virtual,physical',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'variants' => 'nullable|json',
        ]);

        $product = Product::create($validated);

        wncms()->cache()->flush(['products']);

        return redirect()->route('products.edit', $product)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        return view('wncms::backend.products.edit', [
            'page_title' => wncms_model_word('product', 'edit'),
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:virtual,physical',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'variants' => 'nullable|json',
        ]);

        $product->update($validated);

        wncms()->cache()->flush(['products']);

        return redirect()->route('products.edit', $product)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete products.
     */
    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(',', $request->model_ids);

        $count = Product::whereIn('id', $modelIds)->delete();

        return redirect()->route('products.index')
            ->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
