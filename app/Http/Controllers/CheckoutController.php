<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductComponentVariant;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const CLICKER_COMPONENTS = ['base', 'button', 'name'];

    public function create(ProductVariant $variant): View
    {
        $variant->load('product', 'color');
        abort_unless($variant->product->is_active && $variant->product->customization_type === 'standard' && $variant->color->is_active, 404);
        abort_if($variant->availability === 'ready' && $variant->stock < 1, 404);

        return view('storefront.checkout', compact('variant'));
    }

    public function store(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+(). -]{8,24}$/'],
            'shipping_address' => ['required', 'string', 'max:1500'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $phone = self::normalizePhone($validated['customer_phone']);

        $order = DB::transaction(function () use ($variant, $validated, $phone): Order {
            $lockedVariant = ProductVariant::query()->with(['product', 'color'])->lockForUpdate()->findOrFail($variant->id);
            abort_unless($lockedVariant->product->is_active && $lockedVariant->product->customization_type === 'standard' && $lockedVariant->color->is_active, 404);

            if ($lockedVariant->availability === 'ready' && $lockedVariant->stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok ready tidak mencukupi. Silakan kurangi jumlah atau pilih warna lain.',
                ]);
            }

            $subtotal = $lockedVariant->product->price * $validated['quantity'];
            $order = Order::query()->create([
                'code' => '3DP-'.Str::upper(Str::random(10)),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $phone,
                'shipping_address' => $validated['shipping_address'],
                'subtotal' => $subtotal,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
            ]);

            $order->items()->create([
                'product_variant_id' => $lockedVariant->id,
                'product_name' => $lockedVariant->product->name,
                'color_name' => $lockedVariant->color->name,
                'fulfillment_type' => $lockedVariant->availability,
                'quantity' => $validated['quantity'],
                'unit_price' => $lockedVariant->product->price,
                'subtotal' => $subtotal,
                'lead_days' => $lockedVariant->availability === 'po' ? $lockedVariant->lead_days : null,
            ]);

            if ($lockedVariant->availability === 'ready') {
                $lockedVariant->decrement('stock', $validated['quantity']);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending_payment',
            ]);

            return $order;
        });

        return redirect()->route('orders.confirmation', $order->code);
    }

    public function createCustom(Request $request, Product $product): View
    {
        abort_unless($product->is_active && $product->customization_type === 'clicker', 404);
        $product->load(['componentVariants' => fn ($query) => $query->where('is_active', true)->whereHas('color', fn ($colors) => $colors->where('is_active', true))->with('color')]);

        $options = collect(self::CLICKER_COMPONENTS)->mapWithKeys(fn (string $component) => [
            $component => $product->componentVariants
                ->where('component', $component)
                ->filter(fn ($option) => $option->availability === 'po' || $option->stock > 0)
                ->values(),
        ]);
        abort_if($options->contains(fn ($componentOptions) => $componentOptions->isEmpty()), 409, 'Pilihan warna clicker belum disiapkan.');

        $queryCustomization = $request->query('customization', []);
        $selectedColors = [];
        foreach (self::CLICKER_COMPONENTS as $component) {
            $requestedId = (int) ($queryCustomization[$component.'_color_id'] ?? 0);
            $selectedColors[$component] = $options[$component]->firstWhere('id', $requestedId) ?? $options[$component]->first();
        }

        $customName = mb_substr((string) ($queryCustomization['name'] ?? ''), 0, $product->name_max_length);
        $readyStocks = collect($selectedColors)
            ->filter(fn ($option) => $option->availability === 'ready')
            ->pluck('stock');
        $maxReadyQuantity = $readyStocks->isEmpty() ? 20 : min(20, (int) $readyStocks->min());

        return view('storefront.custom-checkout', compact('product', 'options', 'selectedColors', 'customName', 'maxReadyQuantity'));
    }

    public function storeCustom(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->customization_type === 'clicker', 404);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+(). -]{8,24}$/'],
            'shipping_address' => ['required', 'string', 'max:1500'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'customization.name' => ['required', 'string', 'max:'.$product->name_max_length, 'regex:/^[\pL\pN .\'-]+$/u'],
            'customization.base_color_id' => ['required', 'integer', 'min:1'],
            'customization.button_color_id' => ['required', 'integer', 'min:1'],
            'customization.name_color_id' => ['required', 'integer', 'min:1'],
        ], [
            'customization.name.max' => 'Nama maksimal '.$product->name_max_length.' karakter.',
            'customization.name.regex' => 'Nama hanya boleh berisi huruf, angka, spasi, titik, apostrof, dan tanda hubung.',
        ]);

        $phone = self::normalizePhone($validated['customer_phone']);
        $selections = collect(self::CLICKER_COMPONENTS)->mapWithKeys(fn (string $component) => [
            $component => (int) $validated['customization'][$component.'_color_id'],
        ])->all();

        $order = DB::transaction(function () use ($product, $validated, $phone, $selections): Order {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_unless($lockedProduct->is_active && $lockedProduct->customization_type === 'clicker', 404);
            $name = trim($validated['customization']['name']);

            if (mb_strlen($name) > $lockedProduct->name_max_length) {
                throw ValidationException::withMessages([
                    'customization.name' => 'Nama maksimal '.$lockedProduct->name_max_length.' karakter.',
                ]);
            }

            $componentOptions = ProductComponentVariant::query()
                ->where('product_id', $lockedProduct->id)
                ->where('is_active', true)
                ->whereIn('component', self::CLICKER_COMPONENTS)
                ->whereIn('color_id', array_values($selections))
                ->orderBy('id')
                ->lockForUpdate()
                ->with('color')
                ->get()
                ->keyBy('component');

            $snapshot = [];
            $poLeadDays = [];
            foreach ($selections as $component => $colorId) {
                $option = $componentOptions->get($component);

                if (! $option || (int) $option->color_id !== $colorId || ! $option->color->is_active || ($option->availability === 'ready' && $option->stock < $validated['quantity'])) {
                    throw ValidationException::withMessages([
                        'customization.'.$component.'_color_id' => 'Pilihan warna sudah tidak tersedia atau stoknya tidak mencukupi. Silakan pilih ulang.',
                    ]);
                }

                $snapshot[$component] = [
                    'component_variant_id' => $option->id,
                    'color_id' => $option->color_id,
                    'color_name' => $option->color->name,
                    'hex_code' => $option->color->hex_code,
                    'availability' => $option->availability,
                    'lead_days' => $option->availability === 'po' ? $option->lead_days : null,
                ];

                if ($option->availability === 'po') {
                    $poLeadDays[] = $option->lead_days;
                } else {
                    $option->decrement('stock', $validated['quantity']);
                }
            }

            $nameLength = mb_strlen($name);
            $subtotal = $lockedProduct->price * $validated['quantity'];
            $order = Order::query()->create([
                'code' => '3DP-'.Str::upper(Str::random(10)),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $phone,
                'shipping_address' => $validated['shipping_address'],
                'subtotal' => $subtotal,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
            ]);

            $order->items()->create([
                'product_name' => $lockedProduct->name,
                'color_name' => collect($snapshot)->map(fn (array $part, string $key) => ucfirst($key).': '.$part['color_name'])->implode(' · '),
                'fulfillment_type' => $poLeadDays === [] ? 'ready' : 'po',
                'quantity' => $validated['quantity'],
                'unit_price' => $lockedProduct->price,
                'subtotal' => $subtotal,
                'lead_days' => $poLeadDays === [] ? null : max($poLeadDays),
                'customization' => ['name' => $name, 'name_length' => $nameLength, 'components' => $snapshot],
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending_payment',
            ]);

            return $order;
        });

        return redirect()->route('orders.confirmation', $order->code);
    }

    public static function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        if (str_starts_with($normalized, '8')) {
            return '62'.$normalized;
        }

        return $normalized;
    }
}
