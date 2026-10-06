<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — Printlab')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f4ef] text-[#20211f] antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
        <aside class="border-b border-black/5 bg-[#20211f] text-white lg:min-h-screen lg:border-b-0 lg:px-5 lg:py-7">
            <div class="flex items-center justify-between px-5 py-4 lg:block lg:px-0 lg:py-0"><a href="{{ route('admin.dashboard') }}" class="text-sm font-extrabold tracking-[.17em]">PRINTLAB<span class="text-[#d9f36a]">®</span><span class="mt-1 block text-[9px] font-medium tracking-[.24em] text-white/45">STUDIO ADMIN</span></a><span class="lg:hidden">✳</span></div>
            <nav class="flex gap-2 overflow-x-auto px-4 pb-3 text-xs lg:mt-12 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0 lg:text-sm">
                <a href="{{ route('admin.dashboard') }}" class="whitespace-nowrap rounded-xl px-4 py-3 text-white/70 hover:bg-white/10 hover:text-white">◫ &nbsp; Ringkasan</a>
                <a href="{{ route('admin.products.index') }}" class="whitespace-nowrap rounded-xl px-4 py-3 text-white/70 hover:bg-white/10 hover:text-white">◇ &nbsp; Produk & stok</a>
                <a href="{{ route('admin.orders.index') }}" class="whitespace-nowrap rounded-xl px-4 py-3 text-white/70 hover:bg-white/10 hover:text-white">▤ &nbsp; Transaksi</a>
                <a href="{{ route('storefront.index') }}" class="whitespace-nowrap rounded-xl px-4 py-3 text-white/70 hover:bg-white/10 hover:text-white">↗ &nbsp; Lihat toko</a>
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="hidden lg:block lg:pt-10">@csrf<button class="w-full rounded-xl px-4 py-3 text-left text-sm text-white/50 hover:bg-white/10 hover:text-white">Keluar akun ↗</button></form>
        </aside>
        <div class="min-w-0">
            <header class="flex items-center justify-between border-b border-black/5 bg-white/70 px-5 py-4 sm:px-8"><p class="text-sm font-semibold">@yield('heading', 'Ringkasan')</p><div class="flex items-center gap-3"><span class="hidden text-xs text-[#77786f] sm:block">{{ auth()->user()->name }}</span><span class="grid size-9 place-items-center rounded-full bg-[#d9f36a] text-xs font-black">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><form method="POST" action="{{ route('admin.logout') }}" class="lg:hidden">@csrf<button class="text-xs text-[#77786f]">Keluar</button></form></div></header>
            <main class="mx-auto max-w-7xl p-5 sm:p-8">
                @if (session('success'))<div class="mb-5 rounded-xl bg-[#e8f3cf] px-4 py-3 text-sm text-[#47532d]">{{ session('success') }}</div>@endif
                @if ($errors->any())<div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Periksa kembali data yang dimasukkan.</div>@endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
