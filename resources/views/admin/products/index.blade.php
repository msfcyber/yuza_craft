@extends('layouts.admin')
@section('title', 'Produk & stok — Printlab')
@section('heading', 'Produk & stok')
@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Katalog & ketersediaan</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Produk dan stok</h1>
    </div>
    <a href="{{ route('admin.products.create') }}" class="rounded-full bg-[#20211f] px-5 py-3 text-center text-xs font-bold text-white">+ Tambah model</a>
</div>
<div class="mt-7 grid gap-5 xl:grid-cols-[1fr_300px]">
    <div class="space-y-3">
        @forelse ($products as $product)
            <div class="flex flex-col gap-4 rounded-2xl border border-black/5 bg-white p-4 sm:flex-row sm:items-center">
                <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-[#eeede7]">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/'.$product->image_path) }}" class="size-full object-cover" alt="">
                    @else
                        <span class="grid size-full place-items-center text-xl text-black/20">✳</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h2 class="truncate text-sm font-bold">{{ $product->name }}</h2>
                        <span class="rounded-full px-2 py-1 text-[9px] font-bold {{ $product->is_active ? 'bg-[#e8f3cf] text-[#586b31]' : 'bg-gray-100 text-gray-500' }}">{{ $product->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                        @if ($product->customization_type === 'clicker')
                            <span class="rounded-full bg-[#e8e4f4] px-2 py-1 text-[9px] font-bold text-[#65518d]">CLICKER</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-[#77786f]">Rp {{ number_format($product->price, 0, ',', '.') }} · {{ $product->customization_type === 'clicker' ? $product->componentVariants->where('is_active', true)->count().' opsi komponen · nama maks '.$product->name_max_length.' karakter' : $product->variants->count().' varian' }} · {{ $product->model_format ? strtoupper($product->model_format) : 'Belum ada file 3D' }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if ($product->customization_type === 'clicker')
                            @foreach ($product->componentVariants->where('is_active', true) as $variant)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#f4f3ee] px-2 py-1 text-[9px] text-[#66675f]"><i class="size-2 rounded-full" style="background-color: {{ $variant->color->hex_code }}"></i>{{ ['base' => 'Base', 'button' => 'Tombol', 'name' => 'Tulisan'][$variant->component] ?? ucfirst($variant->component) }}: {{ $variant->color->name }}</span>
                            @endforeach
                        @else
                            @foreach ($product->variants as $variant)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#f4f3ee] px-2 py-1 text-[9px] text-[#66675f]"><i class="size-2 rounded-full" style="background-color: {{ $variant->color->hex_code }}"></i>{{ $variant->color->name }} · {{ $variant->availability === 'ready' ? $variant->stock.' ready' : 'PO '.$variant->lead_days.'h' }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('admin.products.edit', $product) }}" class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold hover:bg-[#f5f4ef]">Edit</a>
                    @if ($product->is_active)
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Sembunyikan produk ini dari katalog?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold text-[#9a5647] hover:bg-red-50">Nonaktifkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-8 text-center text-sm text-[#77786f]">Belum ada model produk.</p>
        @endforelse
        {{ $products->links() }}
    </div>
    <aside class="h-fit rounded-2xl border border-black/5 bg-white p-5">
        <p class="text-sm font-bold">Tambah warna</p>
        <p class="mt-1 text-xs text-[#77786f]">Warna bisa dipakai pada varian produk.</p>
        <form method="POST" action="{{ route('admin.colors.store') }}" class="mt-5 space-y-3">
            @csrf
            <input name="name" required maxlength="60" class="form-input" placeholder="Nama warna">
            <div class="flex items-center gap-3">
                <input type="color" name="hex_code" value="#c9b896" class="h-10 w-14 rounded-lg border border-black/10 p-1">
                <span class="text-xs text-[#77786f]">Kode warna</span>
            </div>
            <button class="w-full rounded-full bg-[#20211f] px-4 py-3 text-xs font-bold text-white">Simpan warna</button>
        </form>
    </aside>
</div>
@endsection
