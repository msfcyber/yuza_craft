@extends('layouts.storefront')

@section('title', 'Custom clicker — '.$product->name)

@section('content')
@php($poLeadDays = collect($selectedColors)->map(fn ($option) => $option->productVariant)->filter(fn ($variant) => $variant->availability === 'po')->pluck('lead_days'))
@php($nameMaxLength = min(10, (int) $product->name_max_length))
<section class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-16">
    <a href="{{ route('products.show', $product->slug) }}" class="text-xs font-semibold text-[#77786f]">← Kembali ke kustomisasi</a>
    <div class="mt-7 grid gap-8 lg:grid-cols-[1.2fr_.8fr]">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Clicker custom</p>
            <h1 class="mt-2 text-4xl font-semibold tracking-tight">Hampir jadi milikmu.</h1>
            <p class="mt-3 text-sm text-[#77786f]">Periksa warna, tulisan, dan isi detail pemesanan.</p>
            <form method="POST" action="{{ route('checkout.custom.store', $product->slug) }}" class="mt-8 space-y-5 rounded-[1.8rem] border border-black/5 bg-white p-6 sm:p-8">
                @csrf
                @foreach (['base', 'button', 'name'] as $component)
                    <input type="hidden" name="customization[{{ $component }}_color_id]" value="{{ $selectedColors[$component]->color_id }}">
                @endforeach
                <input type="hidden" name="customization[name]" value="{{ $customName }}">
                @foreach (['customization.name', 'customization.base_color_id', 'customization.button_color_id', 'customization.name_color_id'] as $customizationField)
                    @error($customizationField)<p class="form-error">{{ $message }}</p>@enderror
                @endforeach
                <div class="rounded-2xl bg-[#f7f6f2] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-[#85867d]">Konfigurasi clicker</p><div class="mt-3 space-y-2 text-xs">@foreach (['base' => 'Base', 'button' => 'Tombol', 'name' => 'Warna tulisan'] as $component => $label)<div class="flex items-center justify-between"><span class="text-[#77786f]">{{ $label }}</span><span class="inline-flex items-center gap-2 font-semibold"><i class="size-3 rounded-full" style="background-color: {{ $selectedColors[$component]->color->hex_code }}"></i>{{ $selectedColors[$component]->color->name }}</span></div>@endforeach<div class="flex justify-between border-t border-black/5 pt-2"><span class="text-[#77786f]">Nama embossed</span><strong>{{ $customName }}</strong></div><div class="flex justify-between"><span class="text-[#77786f]">Jumlah tombol</span><strong>{{ mb_strlen($customName) }} / {{ $nameMaxLength }}</strong></div></div><a href="{{ route('checkout.custom.create', ['product' => $product->slug, 'customization' => ['base_color_id' => $selectedColors['base']->color_id, 'button_color_id' => $selectedColors['button']->color_id, 'name_color_id' => $selectedColors['name']->color_id, 'name' => $customName]]) }}" class="mt-4 inline-block text-[10px] font-bold underline">Ubah konfigurasi</a></div>
                <div><label for="customer_name" class="mb-2 block text-xs font-bold">Nama penerima</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="form-input" placeholder="Nama lengkap">@error('customer_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="customer_phone" class="mb-2 block text-xs font-bold">Nomor WhatsApp</label><input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="tel" autocomplete="tel" class="form-input" placeholder="08xx xxxx xxxx">@error('customer_phone')<p class="form-error">{{ $message }}</p>@enderror<p class="mt-2 text-[10px] text-[#85867d]">Empat digit terakhir nomor ini digunakan untuk verifikasi tracking pesanan.</p></div>
                <div><label for="shipping_address" class="mb-2 block text-xs font-bold">Alamat pengiriman</label><textarea id="shipping_address" name="shipping_address" rows="4" required autocomplete="street-address" class="form-input" placeholder="Nama jalan, kota, kode pos">{{ old('shipping_address') }}</textarea>@error('shipping_address')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="quantity" class="mb-2 block text-xs font-bold">Jumlah</label><input id="quantity" name="quantity" type="number" min="1" max="{{ $maxReadyQuantity }}" value="{{ old('quantity', 1) }}" required class="form-input max-w-32">@error('quantity')<p class="form-error">{{ $message }}</p>@enderror @if ($maxReadyQuantity < 20)<p class="mt-2 text-[10px] text-[#85867d]">Jumlah dibatasi stok ready varian warna pilihan (maks. {{ $maxReadyQuantity }}).</p>@endif</div>
                <div class="rounded-2xl bg-[#f7f6f2] p-4 text-xs leading-5 text-[#77786f]">Pembayaran dan pengiriman dikonfirmasi admin secara manual setelah pesanan dibuat.</div>
                <button class="flex w-full items-center justify-between rounded-full bg-[#20211f] px-6 py-4 text-sm font-bold text-white hover:bg-[#41433c]"><span>Buat pesanan custom</span><span>↗</span></button>
            </form>
        </div>
        <aside class="h-fit rounded-[1.8rem] bg-[#eae9e2] p-6 sm:p-7 lg:sticky lg:top-28">
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#7a7b72]">Ringkasan</p>
            <div class="mt-6 flex items-start justify-between gap-3"><h2 class="font-semibold">{{ $product->name }}</h2><p class="text-sm font-bold">Rp {{ number_format($unitPrice, 0, ',', '.') }}</p></div>
            <div class="mt-4 space-y-2 text-xs text-[#65675e]">
                <div class="flex justify-between"><span>Harga awal (1–4 huruf)</span><span>Rp {{ number_format($product->price, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>{{ $additionalCharacterCount }} huruf tambahan × Rp {{ number_format($product->additional_character_price, 0, ',', '.') }}</span><span>Rp {{ number_format($additionalCharacterCount * $product->additional_character_price, 0, ',', '.') }}</span></div>
            </div>
            <div class="mt-5 rounded-xl bg-white/70 p-4 text-xs leading-5 text-[#65675e]">@if ($poLeadDays->isEmpty())Semua komponen ready.@else Pre-order · estimasi hingga {{ $poLeadDays->max() }} hari kerja.@endif</div>
            <div class="my-5 border-t border-black/10"></div>
            <div class="flex justify-between text-sm"><span>Subtotal</span><strong id="checkout-subtotal" data-unit-price="{{ $unitPrice }}">Rp {{ number_format($unitPrice, 0, ',', '.') }}</strong></div>
            <p class="mt-4 text-[10px] leading-4 text-[#85867d]">Harga awal mencakup 1–4 huruf. Setiap huruf mulai huruf ke-5 dikenakan Rp {{ number_format($product->additional_character_price, 0, ',', '.') }}. Biaya pengiriman dikonfirmasi terpisah.</p>
        </aside>
    </div>
</section>
@endsection
