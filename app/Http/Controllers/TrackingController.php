<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PhoneOtp;
use App\Services\WhatsAppOtpSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TrackingController extends Controller
{
    public function index(): View
    {
        $orders = collect();
        $verifiedPhone = session('verified_phone');

        if ($verifiedPhone) {
            $orders = Order::query()->where('customer_phone', $verifiedPhone)->with('items')->latest()->get();
        }

        return view('tracking.index', compact('orders', 'verifiedPhone'));
    }

    public function show(string $code): View
    {
        $order = Order::query()->where('code', $code)->with('items')->firstOrFail();

        return view('tracking.show', compact('order'));
    }

    public function confirmation(string $code): View
    {
        $order = Order::query()->where('code', $code)->with('items')->firstOrFail();

        return view('storefront.confirmation', compact('order'));
    }

    public function sendOtp(Request $request, WhatsAppOtpSender $sender): RedirectResponse
    {
        $validated = $request->validate(['phone' => ['required', 'string', 'max:24']]);
        $phone = CheckoutController::normalizePhone($validated['phone']);
        $key = 'history-otp:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['phone' => 'Terlalu banyak permintaan. Coba lagi beberapa menit.']);
        }
        RateLimiter::hit($key, 300);

        if (Order::query()->where('customer_phone', $phone)->exists()) {
            $code = (string) random_int(100000, 999999);
            PhoneOtp::query()->where('phone', $phone)->delete();
            PhoneOtp::query()->create([
                'phone' => $phone,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(5),
            ]);
            $sender->send($phone, $code);
        }

        return back()->with('otp_sent', 'Jika nomor tersebut memiliki pesanan, kode verifikasi telah dikirim melalui WhatsApp.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:24'],
            'code' => ['required', 'digits:6'],
        ]);
        $phone = CheckoutController::normalizePhone($validated['phone']);
        $otp = PhoneOtp::query()->where('phone', $phone)->latest()->first();

        if (! $otp || $otp->verified_at || $otp->expires_at->isPast() || $otp->attempts >= 5) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        $otp->increment('attempts');

        if (! Hash::check($validated['code'], $otp->code_hash)) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        $otp->update(['verified_at' => now()]);
        $request->session()->put('verified_phone', $phone);

        return redirect()->route('history.index')->with('success', 'Nomor HP berhasil diverifikasi.');
    }

    public function forgetHistory(Request $request): RedirectResponse
    {
        $request->session()->forget('verified_phone');

        return redirect()->route('history.index');
    }
}
