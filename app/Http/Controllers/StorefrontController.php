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
        $product->load([
            'variants' => fn ($query) => $query->whereHas('color', fn ($colors) => $colors->where('is_active', true))->with('color'),
            'componentVariants' => fn ($query) => $query->where('is_active', true)->whereHas('color', fn ($colors) => $colors->where('is_active', true))->with('color'),
        ]);
        $clickerOptions = collect(['base', 'button', 'name'])->mapWithKeys(fn (string $component) => [
            $component => $product->componentVariants
                ->where('component', $component)
                ->filter(fn ($option) => $option->availability === 'po' || $option->stock > 0)
                ->values(),
        ]);
        $clickerAvailable = $clickerOptions->every(fn ($options) => $options->isNotEmpty());

        return view('storefront.show', compact('product', 'clickerOptions', 'clickerAvailable'));
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
