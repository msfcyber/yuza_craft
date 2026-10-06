<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:colors,name'],
            'hex_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        Color::query()->create($data);

        return back()->with('success', 'Warna berhasil ditambahkan.');
    }

    public function destroy(Color $color): RedirectResponse
    {
        abort_if($color->variants()->exists(), 422, 'Warna yang sudah digunakan produk tidak dapat dihapus.');
        $color->update(['is_active' => false]);

        return back()->with('success', 'Warna dinonaktifkan.');
    }
}
