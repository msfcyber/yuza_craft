<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->with('variants.color', 'componentVariants.color')->latest()->paginate(12);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $product = new Product;
        $product->setRelation('componentVariants', collect());
        $colors = Color::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.form', compact('product', 'colors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $product = Product::query()->create($this->productData($request, $data));
        $this->syncVariants($product, $data['variants'] ?? []);
        $this->syncComponentVariants($product, $data['component_variants'] ?? []);

        return redirect()->route('admin.products.index')->with('success', 'Model produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $product->load('variants', 'componentVariants');
        $colors = Color::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.form', compact('product', 'colors'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $product->update($this->productData($request, $data, $product));
        $this->syncVariants($product, $data['variants'] ?? []);
        $this->syncComponentVariants($product, $data['component_variants'] ?? []);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return back()->with('success', 'Produk disembunyikan dari katalog.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
            'customization_type' => ['required', 'in:standard,clicker'],
            'name_max_length' => ['required_if:customization_type,clicker', 'integer', 'min:1', 'max:10'],
            'additional_character_price' => ['required_if:customization_type,clicker', 'integer', 'min:0', 'max:100000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'model_file' => [
                'nullable',
                'file',
                'extensions:stl,3mf',
                'max:51200',
                function (string $attribute, UploadedFile $file, \Closure $fail): void {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $handle = fopen($file->getRealPath(), 'rb');
                    $header = $handle ? fread($handle, 84) : '';

                    if ($handle) {
                        fclose($handle);
                    }

                    $isAsciiStl = $extension === 'stl' && preg_match('/^\s*solid(?:\s|$)/i', $header) === 1;
                    $triangleCount = strlen($header) >= 84 ? (unpack('Vcount', substr($header, 80, 4))['count'] ?? 0) : 0;
                    $isBinaryStl = $extension === 'stl' && strlen($header) === 84 && $triangleCount > 0 && $file->getSize() >= 84 + (50 * $triangleCount);
                    $is3mfArchive = $extension === '3mf' && str_starts_with($header, "PK\x03\x04");

                    if (! $isAsciiStl && ! $isBinaryStl && ! $is3mfArchive) {
                        $fail('File model harus berisi STL yang valid atau arsip 3MF.');
                    }
                },
            ],
            'variants' => ['nullable', 'array'],
            'variants.*.color_id' => ['required', 'integer', 'exists:colors,id'],
            'variants.*.availability' => ['required', 'in:ready,po'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'variants.*.lead_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'component_variants' => ['nullable', 'array'],
            'component_variants.*.*.component' => ['required', 'in:base,button,name'],
            'component_variants.*.*.color_id' => ['required', 'integer', 'exists:colors,id'],
            'component_variants.*.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function productData(Request $request, array $data, ?Product $product = null): array
    {
        $imagePath = $product?->image_path;
        $modelPath = $product?->model_path;
        $modelFormat = $product?->model_format;

        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        if ($request->hasFile('model_file')) {
            if ($modelPath) {
                Storage::disk('local')->delete($modelPath);
            }
            $file = $request->file('model_file');
            $modelPath = $file->store('models', 'local');
            $modelFormat = strtolower($file->getClientOriginalExtension());
        }

        return [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'additional_character_price' => $data['customization_type'] === 'clicker' ? (int) $data['additional_character_price'] : 0,
            'is_active' => $request->boolean('is_active'),
            'customization_type' => $data['customization_type'],
            'name_max_length' => $data['customization_type'] === 'clicker' ? min(10, (int) $data['name_max_length']) : 8,
            'image_path' => $imagePath,
            'model_path' => $modelPath,
            'model_format' => $modelFormat,
        ];
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $data) {
            $product->variants()->updateOrCreate(
                ['color_id' => $data['color_id']],
                [
                    'availability' => $data['availability'],
                    'stock' => $data['availability'] === 'ready' ? (int) ($data['stock'] ?? 0) : 0,
                    'lead_days' => $data['availability'] === 'po' ? (int) ($data['lead_days'] ?? 14) : 14,
                ],
            );
        }
    }

    private function syncComponentVariants(Product $product, array $components): void
    {
        $product->componentVariants()->update(['is_active' => false]);
        $availableColorIds = $product->variants()->pluck('color_id')->all();

        foreach ($components as $options) {
            foreach ($options as $data) {
                if (! in_array((int) $data['color_id'], $availableColorIds, true)) {
                    continue;
                }

                $isActive = (bool) ($data['is_active'] ?? false);
                $product->componentVariants()->updateOrCreate(
                    ['component' => $data['component'], 'color_id' => $data['color_id']],
                    ['is_active' => $isActive],
                );
            }
        }
    }
}
