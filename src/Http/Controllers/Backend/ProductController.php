<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class ProductController extends BackendController
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        // Filters
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $products = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.products.index', [
            'page_title' => wncms_model_word('product', 'management'),
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create($id = null)
    {
        if ($id) {
            $product = $this->modelClass::find($id);
            if (!$product) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $product = new $this->modelClass;
        }

        return $this->view('backend.products.create', [
            'page_title' => wncms_model_word('product', 'create'),
            'product' => $product,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        if ($request->slug) {
            $existingProduct = $this->modelClass::where('slug', $request->slug)->where('id', '!=', $product->id)->first();
            if ($existingProduct) {
                return back()->withMessage(__('wncms::word.slug_already_exists', ['slug' => $slug]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'type' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'is_variable' => 'nullable|boolean',
            'properties' => 'nullable|array',
            'variants' => 'nullable|array',
        ]);

        $product->update([
            'name' => $request->name,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('products'),
            'status' => $request->status ?? 'active',
            'type' => $request->type,
            'price' => $request->price,
            'stock' => $request->stock,
            'is_variable' => $request->is_variable ?? false,
            'properties' => $request->properties ?? [],
            'variants' => $request->variants ?? [],
        ]);


        $this->flush();

        return redirect()->route('products.edit', $product)->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit($id)
    {
        $product = $this->modelClass::find($id);
        if (!$product) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        // dd($product);

        return $this->view('backend.products.edit', [
            'page_title' => wncms_model_word('product', 'edit'),
            'product' => $product,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $product = $this->modelClass::find($id);
        if (!$product) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        if ($request->slug) {
            $existingProduct = $this->modelClass::where('slug', $request->slug)->where('id', '!=', $product->id)->first();
            if ($existingProduct) {
                return back()->withMessage(__('wncms::word.slug_already_exists', ['slug' => $slug]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'type' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'is_variable' => 'nullable|boolean',
            'properties' => 'nullable|array',
            'variants' => 'nullable|array',
        ]);

        $product->update([
            'name' => $request->name,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('products'),
            'status' => $request->status ?? 'active',
            'type' => $request->type,
            'price' => $request->price,
            'stock' => $request->stock,
            'is_variable' => $request->is_variable ?? false,
            'properties' => $request->properties ?? [],
            'variants' => $request->variants ?? [],
        ]);

        $this->flush();

        return redirect()->route('products.edit', $product)->withMessage(__('wncms::word.successfully_updated'));
    }
}
