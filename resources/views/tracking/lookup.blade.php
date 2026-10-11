@extends('layouts.storefront')
@section('title', 'Lacak pesanan — Printlab')
@section('content')
<section class="mx-auto max-w-2xl px-5 py-14 sm:px-8 sm:py-24">
    <div class="rounded-[2rem] border border-black/5 bg-white p-7 sm:p-10">
        <div class="grid size-12 place-items-center rounded-full bg-[#d9f36a] text-xl">↗</div>
        <p class="mt-7 text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Tracking</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Pesananmu di mana?</h1>
        <p class="mt-3 text-sm leading-6 text-[#77786f]">Masukkan kode transaksi dan 4 digit terakhir nomor HP yang digunakan saat memesan.</p>
        <form method="POST" action="{{ route('tracking.verify') }}" class="mt-7 space-y-4">
            @csrf
            <div>
                <label for="code" class="mb-2 block text-xs font-bold">Kode transaksi lengkap</label>
                <input id="code" name="code" value="{{ old('code', $code ?? '') }}" required maxlength="24" class="form-input uppercase tracking-wider" placeholder="Contoh: 3DP-ABC123XYZ">
            </div>
            <div>
                <label for="phone_last_four" class="mb-2 block text-xs font-bold">4 digit terakhir nomor HP</label>
                <input id="phone_last_four" name="phone_last_four" value="{{ old('phone_last_four') }}" required inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="form-input tracking-[.3em]" placeholder="••••">
            </div>
            @if ($errors->any())
                <p class="text-xs text-red-600">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-full bg-[#20211f] px-6 py-3 text-sm font-bold text-white">Lacak pesanan ↗</button>
        </form>
    </div>
</section>
@endsection
