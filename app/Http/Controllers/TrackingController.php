<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrackingController extends Controller
{
    public function index(): View
    {
        $verifiedPhone = session('verified_phone');
        $orders = $verifiedPhone
            ? Order::query()->where('customer_phone', $verifiedPhone)->with('items')->latest()->get()
            : collect();

        return view('tracking.index', compact('orders', 'verifiedPhone'));
    }

    public function verifyHistory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:24', 'regex:/^[0-9+(). -]{8,24}$/'],
            'phone_last_four' => ['required', 'digits:4'],
        ]);
        $phone = CheckoutController::normalizePhone($validated['phone']);

        if (! hash_equals(substr($phone, -4), $validated['phone_last_four'])) {
            throw ValidationException::withMessages([
                'phone_last_four' => 'Nomor HP atau 4 digit terakhir tidak cocok.',
            ]);
        }

        $request->session()->put('verified_phone', $phone);
        $request->session()->forget('tracked_order_code');

        return redirect()->route('history.index');
    }

    public function forgetHistory(Request $request): RedirectResponse
    {
        $request->session()->forget(['verified_phone', 'tracked_order_code']);

        return redirect()->route('history.index');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:24'],
            'phone_last_four' => ['required', 'digits:4'],
        ]);
        $code = strtoupper(trim($validated['code']));
        $order = Order::query()->where('code', $code)->first();

        if (! $order || ! hash_equals(substr($order->customer_phone, -4), $validated['phone_last_four'])) {
            throw ValidationException::withMessages([
                'phone_last_four' => 'Kode transaksi atau 4 digit terakhir nomor HP tidak cocok.',
            ]);
        }

        $request->session()->put('tracked_order_code', $order->code);

        return redirect()->route('tracking.show', $order->code);
    }

    public function show(Request $request, string $code): View
    {
        $order = Order::query()->where('code', $code)->with('items')->firstOrFail();
        $canViewOrder = $request->session()->get('tracked_order_code') === $order->code
            || $request->session()->get('verified_phone') === $order->customer_phone;

        if (! $canViewOrder) {
            return view('tracking.lookup', ['code' => $order->code]);
        }

        return view('tracking.show', compact('order'));
    }

    public function confirmation(string $code): View
    {
        $order = Order::query()->where('code', $code)->with('items')->firstOrFail();

        return view('storefront.confirmation', compact('order'));
    }
}
