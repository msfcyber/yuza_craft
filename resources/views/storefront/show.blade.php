@extends('layouts.storefront')

@section('title', $product->name.' — Printlab')

@section('content')
@php($availableVariant = $product->variants->first(fn ($variant) => $variant->availability === 'po' || $variant->stock > 0))
@php($nameMaxLength = min(10, (int) $product->name_max_length))
<section class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
    <a href="{{ route('storefront.index') }}#shop" class="text-xs font-semibold text-[#77786f] hover:text-black">← Kembali ke koleksi</a>
    <div class="mt-6 grid gap-10 lg:grid-cols-[1.1fr_.9fr]">
        <div class="min-w-0">
            @if ($product->customization_type === 'clicker')
                <div data-3d-viewer data-keycap-generator="true" data-keycap-template-url="{{ $product->model_format === '3mf' && $product->model_path ? route('products.model', $product->slug) : '' }}" data-custom-name="" data-base-color="{{ $clickerOptions['base']->first()?->color->hex_code ?? '#c9b896' }}" data-button-color="{{ $clickerOptions['button']->first()?->color->hex_code ?? '#c9b896' }}" data-name-color="{{ $clickerOptions['name']->first()?->color->hex_code ?? '#c9b896' }}" class="viewer-frame relative aspect-square overflow-hidden rounded-[2rem] border border-black/5 bg-[#e8e7df]">
                    <div class="viewer-loading absolute inset-0 grid place-items-center text-xs text-[#77786f]">Memuat pratinjau 3D…</div>
                    <div data-keycap-empty class="absolute inset-0 hidden place-items-center p-8 text-center text-sm text-[#77786f]">Ketik nama untuk melihat susunan keycap 3D.</div>
                    <div data-keycap-error class="absolute inset-x-4 top-4 hidden rounded-xl bg-white/90 p-3 text-center text-[10px] text-[#77786f]">Model 3D belum dapat dimuat; preview bentuk generik ditampilkan.</div>
                    <div class="absolute bottom-4 left-4 rounded-full bg-white/80 px-3 py-2 text-[10px] font-semibold text-[#66675f] backdrop-blur">Seret untuk memutar · Scroll untuk zoom</div>
                </div>
            @elseif ($product->model_path)
                <div data-3d-viewer data-model-url="{{ route('products.model', $product->slug) }}" data-model-format="{{ $product->model_format }}" data-color="{{ $product->variants->first()?->color->hex_code ?? '#c9b896' }}" class="viewer-frame relative aspect-square overflow-hidden rounded-[2rem] border border-black/5 bg-[#e8e7df]">
                    <div class="viewer-loading absolute inset-0 grid place-items-center text-xs text-[#77786f]">Memuat pratinjau 3D…</div>
                    <div class="viewer-error absolute inset-0 hidden place-items-center p-8 text-center text-sm text-[#77786f]">Pratinjau belum dapat dimuat. Silakan hubungi kami untuk detail model.</div>
                    <div class="absolute bottom-4 left-4 rounded-full bg-white/80 px-3 py-2 text-[10px] font-semibold text-[#66675f] backdrop-blur">Seret untuk memutar · Scroll untuk zoom</div>
                </div>
            @elseif ($product->image_path)
                <div class="aspect-square overflow-hidden rounded-[2rem] bg-[#e8e7df]"><img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="size-full object-cover"></div>
            @else
                <div class="grid aspect-square place-items-center rounded-[2rem] bg-[#e8e7df]"><div class="grid size-56 place-items-center rounded-[38%] bg-[#c9b896] shadow-[inset_-20px_-22px_36px_rgba(30,30,20,.18),18px_24px_36px_rgba(30,30,20,.13)]"><span class="grid size-32 place-items-center rounded-[32%] bg-[#e8e7df] text-4xl text-black/30">✳</span></div></div>
            @endif
            <p class="mt-3 text-center text-[10px] text-[#92938c]">{{ $product->customization_type === 'clicker' ? 'Preview 3D berubah langsung · satu tombol untuk setiap huruf atau angka' : ($product->model_path ? 'Pratinjau interaktif · warna pada render bersifat representatif' : 'Foto produk akan ditampilkan di sini') }}</p>
        </div>
        <div class="lg:py-5">
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">PRINTLAB / OBJECT {{ str_pad((string) $product->id, 2, '0', STR_PAD_LEFT) }}</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-[-.04em] sm:text-5xl">{{ $product->name }}</h1>
            <p class="mt-4 text-2xl font-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="mt-5 max-w-xl text-sm leading-7 text-[#717269]">{{ $product->description }}</p>
            <div class="my-7 border-t border-black/10"></div>

            @if ($product->customization_type === 'clicker')
                <form id="clicker-customizer" method="GET" action="{{ route('checkout.custom.create', $product->slug) }}" class="mt-6 space-y-5">
                    @foreach (['base' => 'Warna base', 'button' => 'Warna tombol', 'name' => 'Warna tulisan'] as $component => $label)
                        <fieldset><legend class="text-xs font-bold uppercase tracking-wider">{{ $label }}</legend><div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($clickerOptions[$component] as $option)
                                <label class="cursor-pointer"><input class="peer sr-only" type="radio" name="customization[{{ $component }}_color_id]" value="{{ $option->color_id }}" data-component="{{ $component }}" data-color="{{ $option->color->hex_code }}" {{ $loop->first ? 'checked' : '' }}><span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-3 py-2 text-xs transition peer-checked:border-[#20211f] peer-checked:bg-white"><i class="size-4 rounded-full border border-black/10" style="background-color: {{ $option->color->hex_code }}"></i>{{ $option->color->name }}<small class="text-[#85867d]">{{ $option->productVariant->availability === 'ready' ? 'Ready · '.$option->productVariant->stock : 'PO · '.$option->productVariant->lead_days.' hari' }}</small></span></label>
                            @endforeach
                        </div></fieldset>
                    @endforeach
                    <div><label for="custom-name" class="mb-2 block text-xs font-bold uppercase tracking-wider">Nama pada tombol</label><input id="custom-name" name="customization[name]" maxlength="{{ $nameMaxLength }}" required class="form-input" placeholder="Contoh: NADIA" autocomplete="off"><div class="mt-2 flex justify-between gap-3 text-[10px] text-[#85867d]"><span>Huruf dan angka tanpa spasi · maks. {{ $nameMaxLength }} tombol</span><span class="shrink-0"><b id="name-length" aria-live="polite">0</b>/{{ $nameMaxLength }} tombol</span></div></div>
                    <div class="rounded-xl bg-[#f1f0ea] p-4"><p class="text-[9px] font-bold uppercase tracking-wider text-[#85867d]">Nama pada keycap</p><p id="custom-name-preview" class="mt-2 min-h-8 break-all text-2xl font-black tracking-wider text-[#6d7847]" style="text-shadow: 1px 1px 0 #fff, 2px 3px 0 rgba(45,45,35,.2)">Nama kamu</p></div>
                    @if ($clickerAvailable)
                        <button class="flex w-full items-center justify-between rounded-full bg-[#20211f] px-6 py-4 text-sm font-bold text-white transition hover:bg-[#41433c]"><span>Pesan clicker custom</span><span class="text-lg">↗</span></button>
                    @else
                        <p class="rounded-xl bg-[#f4eee1] p-4 text-xs text-[#876c3f]">Pilihan warna untuk komponen clicker belum lengkap.</p>
                    @endif
                    <p class="text-[10px] leading-5 text-[#85867d]">Estimasi pesanan mengikuti komponen dengan waktu produksi terlama. Nama dan tiga warna akan tersimpan di transaksi.</p>
                </form>
            @else
                <p class="text-xs font-bold uppercase tracking-wider">Pilih warna</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @forelse ($product->variants as $variant)
                        <label class="variant-choice flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-black/10 bg-white/70 p-3 transition hover:border-black/30 has-[:checked]:border-[#20211f] has-[:checked]:bg-white">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="selected_variant" value="{{ $variant->id }}" data-color="{{ $variant->color->hex_code }}" data-checkout-url="{{ route('checkout.create', $variant) }}" class="sr-only" {{ $availableVariant?->id === $variant->id ? 'checked' : '' }} {{ $variant->availability === 'ready' && $variant->stock < 1 ? 'disabled' : '' }}>
                                <span class="size-7 rounded-full border border-black/10 {{ $variant->availability === 'ready' && $variant->stock < 1 ? 'opacity-40' : '' }}" style="background-color: {{ $variant->color->hex_code }}"></span>
                                <span><span class="block text-sm font-semibold">{{ $variant->color->name }}</span><span class="block pt-1 text-[10px] text-[#7b7c73]">{{ $variant->availability === 'ready' ? ($variant->stock > 0 ? 'Ready · '.$variant->stock.' tersedia' : 'Ready · habis') : 'PO · sekitar '.$variant->lead_days.' hari' }}</span></span>
                            </span>
                            <span class="text-sm">{{ $variant->availability === 'ready' && $variant->stock > 0 ? '●' : '◷' }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-[#77786f]">Warna belum tersedia.</p>
                    @endforelse
                </div>

                <a id="checkout-link" href="{{ $availableVariant ? route('checkout.create', $availableVariant) : '#' }}" class="mt-7 flex w-full items-center justify-between rounded-full bg-[#20211f] px-6 py-4 text-sm font-bold text-white transition hover:bg-[#41433c] {{ ! $availableVariant ? 'pointer-events-none opacity-40' : '' }}"><span>{{ $availableVariant ? 'Lanjut pesan' : 'Stok belum tersedia' }}</span><span class="text-lg">↗</span></a>
                <div class="mt-5 flex gap-3 rounded-2xl bg-[#eeede7] p-4 text-xs leading-5 text-[#6e7067]"><span class="text-base">✳</span><p>Warna ready diproses lebih cepat. Pilihan PO dibuat khusus dan membutuhkan waktu sesuai estimasi.</p></div>
            @endif
        </div>
    </div>
</section>
@endsection
