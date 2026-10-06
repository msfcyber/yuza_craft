<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function create(ProductVariant $variant): View
    {
        $variant->load('product', 'color');
        abort_unless($variant->product->is_active && $variant->color->is_active, 404);
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
            abort_unless($lockedVariant->product->is_active && $lockedVariant->color->is_active, 404);

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
