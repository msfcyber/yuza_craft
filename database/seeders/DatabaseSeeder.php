<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $colors = collect([
            ['name' => 'Sandstone', 'hex_code' => '#C7A982'],
            ['name' => 'Forest', 'hex_code' => '#5B7553'],
            ['name' => 'Ink', 'hex_code' => '#34383B'],
            ['name' => 'Clay', 'hex_code' => '#CE775E'],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Color::query()->updateOrCreate(['name' => $data['name']], $data)]);

        $products = [
            [
                'name' => 'Orbital Planter',
                'slug' => 'orbital-planter',
                'description' => 'Pot tanaman bertekstur dengan siluet lembut, dicetak sesuai pesanan di studio kami.',
                'price' => 89000,
                'variants' => ['Sandstone' => ['ready', 8, 14], 'Forest' => ['ready', 3, 14], 'Ink' => ['po', 0, 10], 'Clay' => ['po', 0, 12]],
            ],
            [
                'name' => 'Flexi Fox',
                'slug' => 'flexi-fox',
                'description' => 'Figur rubah artikulasi yang bisa digerakkan, cocok untuk teman meja kerja.',
                'price' => 125000,
                'variants' => ['Sandstone' => ['po', 0, 14], 'Forest' => ['ready', 5, 14], 'Ink' => ['ready', 2, 14], 'Clay' => ['po', 0, 10]],
            ],
            [
                'name' => 'Arc Desk Dock',
                'slug' => 'arc-desk-dock',
                'description' => 'Organizer minimal untuk merapikan kabel dan aksesori di meja.',
                'price' => 74000,
                'variants' => ['Sandstone' => ['ready', 6, 14], 'Forest' => ['po', 0, 12], 'Ink' => ['ready', 4, 14], 'Clay' => ['po', 0, 14]],
            ],
        ];

        foreach ($products as $data) {
            $variants = $data['variants'];
            unset($data['variants']);
            $product = Product::query()->updateOrCreate(['slug' => $data['slug']], $data + ['is_active' => true]);

            foreach ($variants as $colorName => [$availability, $stock, $leadDays]) {
                $product->variants()->updateOrCreate(
                    ['color_id' => $colors[$colorName]->id],
                    ['availability' => $availability, 'stock' => $stock, 'lead_days' => $leadDays],
                );
            }
        }

        $clicker = Product::query()->updateOrCreate(
            ['slug' => 'personal-clicker'],
            [
                'name' => 'Personal Clicker',
                'description' => 'Clicker custom dengan pilihan warna base, tombol, tulisan, dan nama embossed milikmu.',
                'price' => 99000,
                'is_active' => true,
                'customization_type' => 'clicker',
                'name_max_length' => 8,
            ],
        );
        $clickerVariants = [
            'Sandstone' => ['ready', 8, 14],
            'Forest' => ['ready', 4, 14],
            'Ink' => ['po', 0, 10],
            'Clay' => ['po', 0, 12],
        ];

        foreach ($clickerVariants as $colorName => [$availability, $stock, $leadDays]) {
            $clicker->variants()->updateOrCreate(
                ['color_id' => $colors[$colorName]->id],
                ['availability' => $availability, 'stock' => $stock, 'lead_days' => $leadDays],
            );
        }

        $componentColors = [
            'base' => ['Sandstone', 'Forest', 'Ink', 'Clay'],
            'button' => ['Sandstone', 'Forest', 'Ink', 'Clay'],
            'name' => ['Sandstone', 'Forest', 'Ink', 'Clay'],
        ];

        foreach ($componentColors as $component => $options) {
            foreach ($options as $colorName) {
                $clicker->componentVariants()->updateOrCreate(
                    ['component' => $component, 'color_id' => $colors[$colorName]->id],
                    ['is_active' => true],
                );
            }
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            $admin = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => env('ADMIN_NAME', 'Administrator'), 'password' => Hash::make($password)],
            );
            $admin->forceFill(['is_admin' => true])->save();
        }
    }
}
