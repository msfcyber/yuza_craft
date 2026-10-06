@extends('layouts.storefront')

@section('title', 'Admin login — Printlab')

@section('content')
<section class="mx-auto max-w-md px-5 py-14 sm:py-24">
    <div class="rounded-[2rem] border border-black/5 bg-white p-7 shadow-sm sm:p-10">
        <span class="grid size-11 place-items-center rounded-full bg-[#d9f36a] font-black">P.</span>
        <p class="mt-7 text-[10px] font-bold uppercase tracking-[.2em] text-[#879f32]">Studio admin</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Selamat datang.</h1>
        <p class="mt-2 text-sm text-[#77786f]">Masuk untuk mengelola produk dan pesanan.</p>
        @if ($errors->any())<p class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">
            @csrf
            <div><label class="mb-2 block text-xs font-bold" for="email">Email</label><input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
            <div><label class="mb-2 block text-xs font-bold" for="password">Password</label><input class="form-input" id="password" name="password" type="password" required autocomplete="current-password"></div>
            <button class="w-full rounded-full bg-[#20211f] px-5 py-3.5 text-sm font-bold text-white hover:bg-[#42443f]">Masuk dashboard ↗</button>
        </form>
    </div>
</section>
@endsection
