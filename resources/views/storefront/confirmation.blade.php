@extends('layouts.storefront')

@section('title', 'Pesanan dibuat — '.$order->code)

@section('content')
<section class="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-24">
    <div class="rounded-[2rem] border border-black/5 bg-white p-7 text-center sm:p-12">
        <div class="mx-auto grid size-16 place-items-center rounded-full bg-[#d9f36a] text-2xl">✓</div>
        <p class="mt-6 text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Pesanan berhasil dicatat</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Terima kasih, {{ $order->customer_name }}.</h1>
        <p class="mt-3 text-sm leading-6 text-[#77786f]">Simpan kode transaksi ini. Untuk tracking, kamu akan diminta memasukkan 4 digit terakhir nomor HP yang digunakan saat memesan.</p>
        <div class="mt-7 rounded-2xl bg-[#f3f2ec] p-5"><p class="text-[10px] uppercase tracking-[.15em] text-[#77786f]">Kode transaksi</p><p class="mt-2 text-2xl font-black tracking-[.12em]">{{ $order->code }}</p><p class="mt-2 text-xs text-[#77786f]">{{ $order->items->first()->product_name }} · {{ $order->items->first()->color_name }} · {{ $order->items->first()->quantity }} pcs</p></div>
        @if ($order->items->first()->customization)
            <div class="mt-4 rounded-2xl bg-[#f3f2ec] p-5 text-left"><p class="text-xs font-bold">Nama clicker: “{{ $order->items->first()->customization['name'] }}”</p><p class="mt-1 text-[10px] text-[#77786f]">{{ $order->items->first()->customization['name_length'] }} karakter</p><div class="mt-3 flex flex-wrap justify-center gap-2">@foreach (['base' => 'Base', 'button' => 'Tombol', 'name' => 'Tulisan'] as $component => $label)<span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-1 text-[10px]"><i class="size-2.5 rounded-full" style="background-color: {{ $order->items->first()->customization['components'][$component]['hex_code'] }}"></i>{{ $label }}: {{ $order->items->first()->customization['components'][$component]['color_name'] }}</span>@endforeach</div></div>
        @endif
        <div class="mt-5 flex justify-between text-sm"><span>Total produk</span><strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong></div>
        <p class="mt-4 text-left text-xs leading-5 text-[#77786f]">Pembayaran dan biaya pengiriman akan dikonfirmasi oleh admin melalui WhatsApp. Status awal pesanan: <b>Menunggu pembayaran</b>.</p>
        <a href="{{ route('tracking.show', $order->code) }}" class="mt-8 inline-flex rounded-full bg-[#20211f] px-6 py-3.5 text-sm font-bold text-white">Lacak pesanan ↗</a>
    </div>
</section>
@endsection
