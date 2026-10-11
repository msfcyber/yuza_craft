@extends('layouts.admin')
@section('title', ($product->exists ? 'Edit produk' : 'Tambah model').' — Printlab')
@section('heading', 'Katalog & stok')
@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-[#77786f]">← Kembali ke produk</a>
    <div class="mt-3 flex items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">{{ $product->exists ? 'Perbarui katalog' : 'Katalog baru' }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $product->exists ? $product->name : 'Tambah model' }}</h1>
        </div>
    </div>
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="mt-7 space-y-7 rounded-[1.8rem] border border-black/5 bg-white p-5 sm:p-8">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <section class="rounded-2xl border border-[#879f32]/20 bg-[#f5f7ed] p-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="customization_type" class="mb-2 block text-xs font-bold">Tipe konfigurasi produk</label>
                    <select id="customization_type" name="customization_type" class="form-input">
                        <option value="standard" @selected(old('customization_type', $product->customization_type ?? 'standard') === 'standard')>Produk standar · satu warna</option>
                        <option value="clicker" @selected(old('customization_type', $product->customization_type ?? 'standard') === 'clicker')>Clicker · 3 warna (base, button, name)</option>
                    </select>
                </div>
                <div data-clicker-settings>
                    <label for="name_max_length" class="mb-2 block text-xs font-bold">Batas karakter nama clicker</label>
                    <input id="name_max_length" type="number" name="name_max_length" min="1" max="10" value="{{ old('name_max_length', min(10, $product->name_max_length ?? 8)) }}" class="form-input">
                    <p class="mt-2 text-[10px] text-[#77786f]">Huruf dan angka · maks 10 tombol.</p>
                </div>
            </div>
        </section>
        <section class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-2 block text-xs font-bold">Nama model</label>
                <input id="name" class="form-input" name="name" value="{{ old('name', $product->name) }}" required maxlength="160" placeholder="Contoh: Mini planter">
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="mb-2 block text-xs font-bold">Deskripsi</label>
                <textarea id="description" class="form-input" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>
            <div>
                <label for="price" class="mb-2 block text-xs font-bold">Harga (Rp)</label>
                <input id="price" class="form-input" type="number" name="price" min="1000" value="{{ old('price', $product->price) }}" required>
            </div>
            <div class="flex items-center gap-3 pt-6">
                <input type="hidden" name="is_active" value="0">
                <input class="size-4 accent-[#879f32]" id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm font-semibold">Tampilkan di katalog</label>
            </div>
        </section>
        <section class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="image" class="mb-2 block text-xs font-bold">Foto produk (JPG, PNG, WEBP · maks 5 MB)</label>
                <input id="image" class="form-input file:mr-3 file:rounded-full file:border-0 file:bg-[#eeede7] file:px-3 file:py-1.5 file:text-xs" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                @if ($product->image_path)
                    <p class="mt-2 text-[10px] text-[#77786f]">Foto sudah tersedia.</p>
                @endif
            </div>
            <div>
                <label for="model_file" class="mb-2 block text-xs font-bold">Model 3D (STL atau 3MF · maks 50 MB)</label>
                <input id="model_file" class="form-input file:mr-3 file:rounded-full file:border-0 file:bg-[#eeede7] file:px-3 file:py-1.5 file:text-xs" type="file" name="model_file" accept=".stl,.3mf,model/stl,model/3mf">
                @if ($product->model_format)
                    <p class="mt-2 text-[10px] text-[#77786f]">Model {{ strtoupper($product->model_format) }} sudah diunggah.</p>
                @endif
            </div>
        </section>
        <section>
            <div>
                <h2 class="text-base font-bold">Varian warna</h2>
                <p class="mt-1 text-xs text-[#77786f]">Atur ketersediaan warna atau estimasi hari untuk PO. Warna di sini juga tersedia untuk komponen clicker.</p>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($colors as $color)
                    @php($variant = $product->variants->firstWhere('color_id', $color->id))
                    <div class="grid gap-3 rounded-2xl bg-[#f6f5f0] p-4 sm:grid-cols-[1.2fr_1fr_1fr] sm:items-center">
                        <div class="flex items-center gap-2">
                            <span class="size-5 rounded-full border border-black/10" style="background-color: {{ $color->hex_code }}"></span>
                            <span class="text-sm font-semibold">{{ $color->name }}</span>
                        </div>
                        <input type="hidden" name="variants[{{ $color->id }}][color_id]" value="{{ $color->id }}">
                        <div>
                            <label for="availability-{{ $color->id }}" class="mb-1 block text-[9px] font-bold uppercase text-[#77786f]">Tipe</label>
                            <select id="availability-{{ $color->id }}" name="variants[{{ $color->id }}][availability]" class="form-input !py-2 text-xs">
                                <option value="ready" @selected(old('variants.'.$color->id.'.availability', $variant?->availability) === 'ready')>Ready</option>
                                <option value="po" @selected(old('variants.'.$color->id.'.availability', $variant?->availability ?? 'po') === 'po')>Pre-order</option>
                            </select>
                        </div>
                        <input type="hidden" name="variants[{{ $color->id }}][stock]" value="{{ old('variants.'.$color->id.'.stock', $variant?->stock ?? 0) }}">
                        <div>
                            <label for="lead-days-{{ $color->id }}" class="mb-1 block text-[9px] font-bold uppercase text-[#77786f]">PO (hari)</label>
                            <input id="lead-days-{{ $color->id }}" type="number" min="1" max="365" name="variants[{{ $color->id }}][lead_days]" value="{{ old('variants.'.$color->id.'.lead_days', $variant?->lead_days ?? 14) }}" class="form-input !py-2 text-xs">
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-[#f6f5f0] p-4 text-sm text-[#77786f]">Tambahkan warna terlebih dahulu dari halaman Produk & stok.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-black/5 bg-[#f7f6f2] p-5" data-clicker-settings>
            <div>
                <h2 class="text-base font-bold">Pilihan warna komponen</h2>
                <p class="mt-1 text-xs leading-5 text-[#77786f]">Pilih warna yang tersedia untuk base, tombol, dan tulisan. Ketersediaan mengikuti varian warna produk.</p>
            </div>
            <div class="mt-5 space-y-5">
                @foreach (['base' => 'Base', 'button' => 'Tombol', 'name' => 'Tulisan nama'] as $componentKey => $componentLabel)
                    <fieldset>
                        <legend class="mb-2 text-xs font-bold uppercase tracking-wider">{{ $componentLabel }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($colors as $color)
                                @php($componentOption = $product->componentVariants->first(fn ($option) => $option->component === $componentKey && $option->color_id === $color->id))
                                @php($isSelected = old('component_variants.'.$componentKey.'.'.$color->id.'.is_active', $componentOption?->is_active ?? false))
                                <label class="cursor-pointer">
                                    <input type="hidden" name="component_variants[{{ $componentKey }}][{{ $color->id }}][component]" value="{{ $componentKey }}">
                                    <input type="hidden" name="component_variants[{{ $componentKey }}][{{ $color->id }}][color_id]" value="{{ $color->id }}">
                                    <input class="peer sr-only" type="checkbox" name="component_variants[{{ $componentKey }}][{{ $color->id }}][is_active]" value="1" @checked($isSelected)>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-2 text-xs font-semibold transition peer-checked:border-[#20211f] peer-checked:ring-2 peer-checked:ring-[#d9f36a]/70 peer-focus-visible:ring-2 peer-focus-visible:ring-[#849b36]"><i class="size-4 rounded-full border border-black/10" style="background-color: {{ $color->hex_code }}"></i>{{ $color->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
            <p class="mt-4 rounded-xl bg-white p-3 text-[10px] leading-4 text-[#77786f]">Untuk generator nama, unggah satu file 3MF berisi mesh terpisah bernama <b>base</b>, <b>button</b>, dan <b>huruf</b>. Ketiganya akan diwarnai sesuai pilihan komponen; preview menggandakan keycap dan mengganti huruf untuk setiap karakter nama. STL tetap bisa dipakai, tetapi warna per bagian memerlukan 3MF.</p>
        </section>
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <div class="flex flex-col-reverse justify-end gap-3 border-t border-black/5 pt-5 sm:flex-row">
            <a href="{{ route('admin.products.index') }}" class="rounded-full border border-black/10 px-6 py-3 text-center text-xs font-bold">Batal</a>
            <button class="rounded-full bg-[#20211f] px-7 py-3 text-xs font-bold text-white">{{ $product->exists ? 'Simpan perubahan' : 'Simpan model' }} ↗</button>
        </div>
    </form>
</div>
@endsection
