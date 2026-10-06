<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Printlab — benda baik, dibuat perlahan')</title>
    <meta name="description" content="Objek 3D print pilihan, dibuat dengan penuh perhatian di studio Printlab.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f6f2] text-[#20211f] antialiased">
    <header class="sticky top-0 z-40 border-b border-black/5 bg-[#f7f6f2]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
            <a href="{{ route('storefront.index') }}" class="flex items-center gap-3" aria-label="Printlab beranda">
                <span class="grid size-9 place-items-center rounded-full bg-[#d9f36a] text-sm font-black">P.</span>
                <span class="text-sm font-extrabold tracking-[.18em]">PRINTLAB<span class="text-[#a2a39b]">®</span></span>
            </a>
            <nav class="flex items-center gap-5 text-xs font-semibold sm:gap-8 sm:text-sm">
                <a href="{{ route('storefront.index') }}#shop" class="hover:text-[#879f32]">Koleksi</a>
                <a href="{{ route('tracking.lookup') }}" class="hover:text-[#879f32]">Lacak pesanan</a>
                <a href="{{ route('history.index') }}" class="rounded-full border border-black/10 px-4 py-2 hover:bg-white">Riwayat</a>
            </nav>
        </div>
    </header>

    @if (session('success'))
        <div class="mx-auto mt-5 max-w-7xl px-5 sm:px-8"><div class="rounded-2xl bg-[#e8f3cf] px-5 py-3 text-sm text-[#47532d]">{{ session('success') }}</div></div>
    @endif

    <main>@yield('content')</main>

    <footer class="mt-24 border-t border-black/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-xs text-[#77786f] sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <p>© {{ date('Y') }} Printlab Studio. Dirancang kecil, dibuat dengan perhatian.</p>
            <a href="{{ route('admin.login') }}" class="hover:text-black">Admin</a>
        </div>
    </footer>
</body>
</html>
