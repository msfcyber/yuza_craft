<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductComponentVariant;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $orders = Order::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($builder) => $builder
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('customer_phone', 'like', '%'.$search.'%')
                ->orWhere('customer_name', 'like', '%'.$search.'%')))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load('items', 'statusHistories.user');

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending_payment,paid,processing,ready,completed,cancelled'],
            'payment_status' => ['required', 'in:unpaid,confirmed'],
        ]);

        DB::transaction(function () use ($order, $data, $request): void {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);
            $oldStatus = $lockedOrder->status;

            if ($oldStatus === 'cancelled' && $data['status'] !== 'cancelled') {
                abort(422, 'Pesanan yang dibatalkan tidak dapat diaktifkan kembali.');
            }

            if ($data['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($lockedOrder->items as $item) {
                    if ($item->customization) {
                        foreach ($item->customization['components'] ?? [] as $component) {
                            if ($component['availability'] === 'ready' && isset($component['component_variant_id'])) {
                                ProductComponentVariant::query()
                                    ->lockForUpdate()
                                    ->find($component['component_variant_id'])
                                    ?->increment('stock', $item->quantity);
                            }
                        }

                        continue;
                    }

                    if ($item->fulfillment_type !== 'ready') {
                        continue;
                    }

                    if ($item->product_variant_id) {
                        $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);
                        $variant?->increment('stock', $item->quantity);
                    }
                }
                $lockedOrder->cancelled_at = now();
            }

            $lockedOrder->update($data);
            $lockedOrder->statusHistories()->create([
                'user_id' => $request->user()->id,
                'from_status' => $oldStatus,
                'to_status' => $data['status'],
            ]);
        });

        return back()->with('success', 'Status pesanan diperbarui.');
    }
}
