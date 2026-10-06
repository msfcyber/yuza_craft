@extends('layouts.storefront')

@section('title', 'Checkout — '.$variant->product->name)

@section('content')
<section class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-16">
    <a href="{{ route('products.show', $variant->product->slug) }}" class="text-xs font-semibold text-[#77786f]">← Kembali ke produk</a>
    <div class="mt-7 grid gap-8 lg:grid-cols-[1.2fr_.8fr]">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Satu langkah lagi</p>
            <h1 class="mt-2 text-4xl font-semibold tracking-tight">Detail pesanan</h1>
            <p class="mt-3 text-sm text-[#77786f]">Tidak perlu membuat akun. Kode transaksi akan dikirim setelah pesanan dibuat.</p>
            <form method="POST" action="{{ route('checkout.store', $variant) }}" class="mt-8 space-y-5 rounded-[1.8rem] border border-black/5 bg-white p-6 sm:p-8">
                @csrf
                <div><label for="customer_name" class="mb-2 block text-xs font-bold">Nama lengkap</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="form-input" placeholder="Nama penerima">@error('customer_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="customer_phone" class="mb-2 block text-xs font-bold">Nomor WhatsApp</label><input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="tel" autocomplete="tel" class="form-input" placeholder="08xx xxxx xxxx">@error('customer_phone')<p class="form-error">{{ $message }}</p>@enderror<p class="mt-2 text-[10px] text-[#85867d]">Nomor ini digunakan untuk OTP dan riwayat transaksi.</p></div>
                <div><label for="shipping_address" class="mb-2 block text-xs font-bold">Alamat pengiriman</label><textarea id="shipping_address" name="shipping_address" rows="4" required autocomplete="street-address" class="form-input" placeholder="Nama jalan, kota, kode pos">{{ old('shipping_address') }}</textarea>@error('shipping_address')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="quantity" class="mb-2 block text-xs font-bold">Jumlah</label><input id="quantity" name="quantity" type="number" min="1" max="{{ $variant->availability === 'ready' ? min(20, $variant->stock) : 20 }}" value="{{ old('quantity', 1) }}" required class="form-input max-w-32">@error('quantity')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="rounded-2xl bg-[#f7f6f2] p-4 text-xs leading-5 text-[#77786f]">Pembayaran dilakukan melalui transfer/manual setelah pesanan dibuat. Admin akan menghubungi Anda untuk detail pembayaran dan pengiriman.</div>
                <button class="flex w-full items-center justify-between rounded-full bg-[#20211f] px-6 py-4 text-sm font-bold text-white hover:bg-[#41433c]"><span>Buat pesanan</span><span>↗</span></button>
            </form>
        </div>
        <aside class="h-fit rounded-[1.8rem] bg-[#eae9e2] p-6 sm:p-7 lg:sticky lg:top-28">
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#7a7b72]">Ringkasan</p>
            <div class="mt-6 flex items-start justify-between gap-3"><div><h2 class="font-semibold">{{ $variant->product->name }}</h2><p class="mt-1 text-xs text-[#77786f]">Warna {{ $variant->color->name }}</p></div><p class="text-sm font-bold">Rp {{ number_format($variant->product->price, 0, ',', '.') }}</p></div>
            <div class="mt-5 rounded-xl bg-white/70 p-4 text-xs leading-5 text-[#65675e]">{{ $variant->availability === 'ready' ? 'Ready sekarang · '.$variant->stock.' unit tersedia' : 'Pre-order · estimasi '.$variant->lead_days.' hari kerja' }}</div>
            <div class="my-5 border-t border-black/10"></div>
            <div class="flex justify-between text-sm"><span>Subtotal</span><strong id="checkout-subtotal" data-unit-price="{{ $variant->product->price }}">Rp {{ number_format($variant->product->price, 0, ',', '.') }}</strong></div>
            <p class="mt-4 text-[10px] leading-4 text-[#85867d]">Biaya pengiriman akan dikonfirmasi admin secara manual setelah pesanan dibuat.</p>
        </aside>
    </div>
</section>
@endsection
