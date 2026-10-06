@extends('layouts.storefront')

@section('title', 'Printlab — objek 3D print untuk keseharian')

@section('content')
    <section class="relative overflow-hidden">
        <div class="absolute -right-28 -top-20 size-96 rounded-full bg-[#d9f36a]/35 blur-3xl"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.1fr_.9fr] lg:py-24">
            <div class="relative z-10">
                <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-4 py-2 text-[11px] font-bold uppercase tracking-[.16em] text-[#65675d]"><span class="size-2 rounded-full bg-[#94b542]"></span> Objek kecil, dibuat dengan perhatian</p>
                <h1 class="max-w-3xl text-5xl font-semibold leading-[.98] tracking-[-.055em] sm:text-7xl">Benda baik,<br>dibuat <span class="font-serif italic text-[#879f32]">perlahan.</span></h1>
                <p class="mt-7 max-w-lg text-base leading-7 text-[#6c6d65]">Objek fungsional dan menyenangkan yang dicetak satu per satu. Pilih warna ready untuk segera dibuat, atau pesan warna favoritmu.</p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="#shop" class="rounded-full bg-[#20211f] px-6 py-3.5 text-sm font-bold text-white transition hover:bg-[#42443f]">Lihat koleksi <span class="ml-3">↘</span></a>
                    <span class="text-xs text-[#818278]">Dirancang lokal · Dicetak sesuai pesanan</span>
                </div>
                <div class="mt-14 flex gap-8 border-t border-black/10 pt-5 text-xs text-[#75766f]"><span><b class="text-lg text-[#22231f]">03</b><br>objek pilihan</span><span><b class="text-lg text-[#22231f]">100%</b><br>made to order</span><span><b class="text-lg text-[#22231f]">∞</b><br>kemungkinan warna</span></div>
            </div>
            <div class="relative mx-auto aspect-[.95] w-full max-w-[480px]">
                <div class="absolute inset-5 rotate-[-5deg] rounded-[2.5rem] bg-[#e9e7dc]"></div>
                <div class="absolute inset-0 overflow-hidden rounded-[2.5rem] border border-black/5 bg-[#e4e2d7]">
                    <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(#72756a 1px, transparent 1px); background-size: 18px 18px"></div>
                    <div class="absolute left-[17%] top-[16%] h-52 w-52 rounded-[44%_56%_52%_48%] bg-gradient-to-br from-[#f3d2ad] to-[#bf8e66] shadow-[inset_-18px_-22px_35px_rgba(62,42,28,.23),18px_26px_36px_rgba(50,40,20,.17)] sm:h-64 sm:w-64"></div>
                    <div class="absolute left-[25%] top-[26%] h-32 w-32 rounded-[42%_58%_46%_54%] bg-[#e4e2d7] shadow-[inset_8px_10px_20px_rgba(55,48,36,.14)] sm:h-40 sm:w-40"></div>
                    <div class="absolute bottom-[14%] left-[23%] h-24 w-40 rotate-[-8deg] rounded-[45%_45%_20%_20%] bg-gradient-to-br from-[#83916a] to-[#43533f] shadow-[0_22px_26px_rgba(30,40,20,.2)] sm:h-28 sm:w-48"></div>
                    <div class="absolute bottom-[7%] right-[8%] rounded-full border border-black/10 bg-[#f7f6f2]/85 px-4 py-3 text-xs font-semibold shadow-sm">Bentuk sederhana, hari lebih baik <span class="ml-2 text-[#839d35]">✳</span></div>
                </div>
                <span class="absolute -right-3 top-8 grid size-16 place-items-center rounded-full bg-[#d9f36a] text-center text-[10px] font-black uppercase leading-3 tracking-wide shadow-lg sm:-right-7">made<br>for you</span>
            </div>
        </div>
    </section>

    <section id="shop" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-12 sm:px-8 sm:py-16">
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-[11px] font-bold uppercase tracking-[.2em] text-[#879f32]">Koleksi 01 / 2026</p><h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Objek sehari-hari</h2></div>
            <p class="max-w-sm text-sm leading-6 text-[#77786f]">Setiap model dibuat dengan teliti. Warna ready dikirim lebih cepat, warna lainnya masuk antrean PO.</p>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-3xl border border-dashed border-black/15 bg-white/50 p-12 text-center text-sm text-[#77786f]">Koleksi sedang disiapkan. Silakan kembali lagi sebentar.</div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $index => $product)
                    @php($readyStock = $product->variants->where('availability', 'ready')->sum('stock'))
                    <a href="{{ route('products.show', $product->slug) }}" class="group rounded-[1.6rem] border border-black/[.06] bg-white p-3 transition duration-300 hover:-translate-y-1 hover:shadow-[0_20px_55px_rgba(38,40,30,.09)]">
                        <div class="relative aspect-[1.14] overflow-hidden rounded-[1.2rem] {{ ['bg-[#e6e5dc]', 'bg-[#e6ebe1]', 'bg-[#e7e2dc]'][$index % 3] }}">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="size-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 grid place-items-center">
                                    <div class="grid size-40 place-items-center rounded-[36%] {{ ['bg-[#c8aa82]', 'bg-[#849373]', 'bg-[#51575a]'][$index % 3] }} shadow-[inset_-16px_-20px_30px_rgba(30,30,20,.18),12px_20px_28px_rgba(30,30,20,.13)] transition duration-500 group-hover:rotate-6 group-hover:scale-105">
                                        <span class="grid size-24 place-items-center rounded-[32%] bg-[#e6e5dc]/90 text-3xl text-black/30">✳</span>
                                    </div>
                                </div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-full bg-white/85 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-[#56584f] backdrop-blur">{{ $readyStock > 0 ? 'Ready · '.$readyStock : 'Made to order' }}</span>
                            <span class="absolute bottom-3 right-3 grid size-10 place-items-center rounded-full bg-[#d9f36a] text-lg transition group-hover:rotate-[-35deg]">↗</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 px-2 pb-2 pt-4">
                            <div><h3 class="font-semibold tracking-tight">{{ $product->name }}</h3><p class="mt-1 line-clamp-2 text-xs leading-5 text-[#82837a]">{{ $product->description }}</p></div>
                            <p class="shrink-0 text-sm font-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        <div class="flex gap-1.5 px-2 pb-2 pt-1">
                            @foreach ($product->variants->take(5) as $variant)
                                <span title="{{ $variant->color->name }}" class="size-3.5 rounded-full border border-black/10" style="background-color: {{ $variant->color->hex_code }}"></span>
                            @endforeach
                            <span class="ml-1 text-[10px] text-[#92938c]">{{ $product->variants->count() }} warna</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-5 pb-8 sm:px-8">
        <div class="flex flex-col gap-6 rounded-[2rem] bg-[#d9f36a] px-7 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-10 sm:py-10">
            <div><p class="text-[10px] font-black uppercase tracking-[.2em] text-[#606e2c]">Pesananmu, jelas arahnya</p><h2 class="mt-2 text-2xl font-semibold tracking-tight">Sudah pesan? Cek statusnya.</h2></div>
            <a href="{{ route('tracking.lookup') }}" class="inline-flex items-center justify-center gap-5 self-start rounded-full bg-[#20211f] px-6 py-3 text-sm font-bold text-white sm:self-auto">Lacak transaksi <span>↗</span></a>
        </div>
    </section>
@endsection
