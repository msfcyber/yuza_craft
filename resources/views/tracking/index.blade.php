<div>
    <!-- Nothing worth having comes easy. - Theodore Roosevelt -->
</div>
@extends('layouts.storefront')
@section('title', 'Riwayat pesanan — Printlab')
@section('content')
<section class="mx-auto max-w-3xl px-5 py-12 sm:px-8 sm:py-20">
    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Area pelanggan</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Riwayat pesanan</h1>
    <p class="mt-3 text-sm leading-6 text-[#77786f]">Masukkan nomor HP lengkap yang digunakan saat memesan dan 4 digit terakhir nomor tersebut untuk melihat semua transaksimu.</p>

    @if ($verifiedPhone)
        <div class="mt-7 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#e8f3cf] px-5 py-4">
            <p class="text-xs font-semibold text-[#526428]">Menampilkan pesanan untuk +{{ $verifiedPhone }}</p>
            <form method="POST" action="{{ route('history.forget') }}">
                @csrf
                @method('DELETE')
                <button class="text-xs underline">Keluar dari riwayat</button>
            </form>
        </div>
        <div class="mt-5 space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('tracking.show', $order->code) }}" class="block rounded-2xl border border-black/5 bg-white p-5 hover:shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold">{{ $order->code }}</p>
                            <p class="mt-1 text-xs text-[#77786f]">{{ $order->created_at->format('d M Y') }} · {{ $order->items->first()->product_name ?? '' }}</p>
                        </div>
                        <span class="rounded-full bg-[#f2f1eb] px-3 py-1.5 text-[10px] font-semibold">{{ ['pending_payment' => 'Menunggu pembayaran', 'paid' => 'Dibayar', 'processing' => 'Diproses', 'ready' => 'Siap dikirim', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$order->status] }}</span>
                    </div>
                    <p class="mt-4 text-sm font-bold">Rp {{ number_format($order->subtotal, 0, ',', '.') }} <span class="float-right font-normal text-[#77786f]">Lihat detail ↗</span></p>
                </a>
            @empty
                <p class="rounded-2xl bg-white p-8 text-center text-sm text-[#77786f]">Belum ada transaksi dengan nomor HP ini.</p>
            @endforelse
        </div>
    @else
        <form method="POST" action="{{ route('history.verify') }}" class="mt-7 space-y-4 rounded-[1.8rem] border border-black/5 bg-white p-6 sm:p-8">
            @csrf
            <div>
                <label for="phone" class="mb-2 block text-xs font-bold">Nomor HP lengkap</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" class="form-input" placeholder="08xx xxxx xxxx">
                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone_last_four" class="mb-2 block text-xs font-bold">4 digit terakhir nomor HP</label>
                <input id="phone_last_four" name="phone_last_four" value="{{ old('phone_last_four') }}" required inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="form-input tracking-[.3em]" placeholder="••••">
                @error('phone_last_four')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <button class="w-full rounded-full bg-[#20211f] px-6 py-3 text-sm font-bold text-white">Lihat semua transaksi ↗</button>
        </form>
    @endif
</section>
@endsection
