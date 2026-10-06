@extends('layouts.admin')
@section('title', 'Ringkasan — Printlab')
@section('heading', 'Ringkasan toko')
@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Hari ini / {{ now()->translatedFormat('d F Y') }}</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Halo, {{ auth()->user()->name }}.</h1></div><a href="{{ route('admin.products.create') }}" class="rounded-full bg-[#20211f] px-5 py-3 text-center text-xs font-bold text-white">+ Tambah model</a></div>
<div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([['Produk aktif', $stats['products'], '◈'], ['Total transaksi', $stats['orders'], '▤'], ['Menunggu pembayaran', $stats['pending'], '◷'], ['Pembayaran dikonfirmasi', 'Rp '.number_format($stats['revenue'], 0, ',', '.'), '↗']] as [$label, $value, $icon])
        <div class="rounded-2xl border border-black/5 bg-white p-5"><div class="flex justify-between text-xs text-[#77786f]"><span>{{ $label }}</span><span>{{ $icon }}</span></div><p class="mt-6 text-2xl font-bold tracking-tight">{{ $value }}</p></div>
    @endforeach
</div>
<div class="mt-10 flex items-center justify-between"><h2 class="text-lg font-semibold">Pesanan terbaru</h2><a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-[#73756b]">Semua transaksi ↗</a></div>
<div class="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
    @forelse ($recentOrders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" class="flex flex-col gap-2 border-b border-black/5 px-5 py-4 last:border-0 hover:bg-[#faf9f5] sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-bold">{{ $order->code }} <span class="font-normal text-[#77786f]">· {{ $order->customer_name }}</span></p><p class="mt-1 text-xs text-[#85867d]">{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->customer_phone }}</p></div><div class="flex items-center gap-3"><span class="rounded-full bg-[#f2f1eb] px-3 py-1.5 text-[10px] font-semibold">{{ str_replace('_', ' ', $order->status) }}</span><span class="text-sm font-bold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div></a>
    @empty
        <p class="p-8 text-center text-sm text-[#77786f]">Belum ada transaksi.</p>
    @endforelse
</div>
@endsection
