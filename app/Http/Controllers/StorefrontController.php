<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['variants' => fn ($query) => $query->whereHas('color', fn ($colors) => $colors->where('is_active', true))->with('color')])
            ->latest()
            ->get();

        return view('storefront.index', compact('products'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load(['variants' => fn ($query) => $query->whereHas('color', fn ($colors) => $colors->where('is_active', true))->with('color')]);

        return view('storefront.show', compact('product'));
    }

    public function model(Product $product): BinaryFileResponse
    {
        abort_unless($product->is_active && $product->model_path && Storage::disk('local')->exists($product->model_path), 404);

        return response()->file(Storage::disk('local')->path($product->model_path), [
            'Content-Type' => $product->model_format === '3mf' ? 'model/3mf' : 'model/stl',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
