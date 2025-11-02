<?php

namespace App\Http\Controllers;

use App\Actions\Product\CreateProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::with('agencies')
            ->latest()
            ->paginate(20);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();

        return Inertia::render('Products/Create', [
            'agencies' => $agencies,
        ]);
    }

    public function store(StoreProductRequest $request, CreateProductAction $action)
    {
        try {
            $action->execute(
                $request->except(['agency_ids', 'agency_prices']),
                $request->agency_ids ?? [],
                $request->agency_prices ?? []
            );

            return redirect()->route('products.index')
                ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create product: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Product $product): Response
    {
        $product->load(['agencies']);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();
        $product->load('agencies');
        
        $agencyPrices = [];
        foreach ($product->agencies as $agency) {
            $agencyPrices[$agency->id] = $agency->pivot->price;
        }

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'agencies' => $agencies,
            'agencyPrices' => $agencyPrices,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action)
    {
        try {
            $action->execute(
                $product,
                $request->except(['agency_ids', 'agency_prices']),
                $request->agency_ids ?? [],
                $request->agency_prices ?? []
            );

            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update product: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
}
