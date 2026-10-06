@extends('layouts.admin')
@section('title', 'Transaksi — Printlab')
@section('heading', 'Transaksi')
@section('content')
<div><p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Semua pesanan</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Transaksi</h1></div>
<form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-black/5 bg-white p-4 sm:grid-cols-[1fr_220px_auto]"><input name="q" value="{{ request('q') }}" class="form-input" placeholder="Cari kode, nama, atau nomor HP"><select name="status" class="form-input"><option value="">Semua status</option>@foreach (['pending_payment' => 'Menunggu pembayaran', 'paid' => 'Dibayar', 'processing' => 'Diproses', 'ready' => 'Siap dikirim/diambil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select><button class="rounded-full bg-[#20211f] px-6 py-3 text-xs font-bold text-white">Cari</button></form>
<div class="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
    @forelse ($orders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" class="grid gap-3 border-b border-black/5 px-5 py-4 last:border-0 hover:bg-[#faf9f5] sm:grid-cols-[1fr_1fr_160px_160px]"><div><p class="text-sm font-bold">{{ $order->code }}</p><p class="mt-1 text-xs text-[#77786f]">{{ $order->customer_name }} · {{ $order->customer_phone }}</p></div><div class="text-xs text-[#77786f]">{{ $order->created_at->format('d M Y · H:i') }}<br><span class="text-[#383a35]">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div><div><span class="rounded-full bg-[#f2f1eb] px-3 py-1.5 text-[10px] font-semibold">{{ ['pending_payment' => 'Menunggu pembayaran', 'paid' => 'Dibayar', 'processing' => 'Diproses', 'ready' => 'Siap dikirim', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$order->status] }}</span></div><div class="text-[10px] font-semibold {{ $order->payment_status === 'confirmed' ? 'text-[#637d2c]' : 'text-[#9a7350]' }}">{{ $order->payment_status === 'confirmed' ? '● Pembayaran dikonfirmasi' : '◷ Belum dibayar' }}</div></a>
    @empty
        <p class="p-10 text-center text-sm text-[#77786f]">Tidak ada pesanan yang cocok.</p>
    @endforelse
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
