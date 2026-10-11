<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductComponentVariant;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_displays_active_products(): void
    {
        $product = Product::factory()->create(['name' => 'Orbital Planter']);
        $color = Color::factory()->create(['name' => 'Sandstone']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'color_id' => $color->id]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Orbital Planter')
            ->assertSee('Sandstone');
    }

    public function test_ready_order_snapshots_selected_variant_and_decrements_stock(): void
    {
        $product = Product::factory()->create(['price' => 89000]);
        $color = Color::factory()->create(['name' => 'Clay']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'ready',
            'stock' => 3,
        ]);

        $response = $this->post(route('checkout.store', $variant), [
            'customer_name' => 'Nadia Putri',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati 12, Bandung',
            'quantity' => 2,
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.confirmation', $order->code));
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame('6281234567890', $order->customer_phone);
        $this->assertSame(178000, $order->subtotal);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => $product->name,
            'color_name' => 'Clay',
            'quantity' => 2,
            'unit_price' => 89000,
            'fulfillment_type' => 'ready',
        ]);
    }

    public function test_ready_order_cannot_exceed_available_stock(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'ready',
            'stock' => 1,
        ]);

        $this->from(route('checkout.create', $variant))
            ->post(route('checkout.store', $variant), [
                'customer_name' => 'Nadia Putri',
                'customer_phone' => '081234567890',
                'shipping_address' => 'Jl. Melati 12, Bandung',
                'quantity' => 2,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_preorder_does_not_decrement_ready_stock(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'po',
            'stock' => 0,
            'lead_days' => 12,
        ]);

        $response = $this->post(route('checkout.store', $variant), [
            'customer_name' => 'Nadia Putri',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati 12, Bandung',
            'quantity' => 1,
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.confirmation', $order->code));
        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'fulfillment_type' => 'po', 'lead_days' => 12]);
    }

    public function test_admin_area_requires_an_admin_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_customer_tracks_order_with_transaction_code_and_last_four_phone_digits(): void
    {
        $order = Order::factory()->create([
            'code' => '3DP-ORDER1234',
            'customer_phone' => '6281234567890',
        ]);
        $this->get(route('tracking.show', $order->code))
            ->assertSee('4 digit terakhir nomor HP')
            ->assertDontSee($order->customer_name);
        $this->post(route('tracking.verify'), [
            'code' => $order->code,
            'phone_last_four' => '7890',
        ])->assertRedirect(route('tracking.show', $order->code))
            ->assertSessionHas('tracked_order_code', $order->code);

        $this->get(route('tracking.show', $order->code))->assertSee($order->customer_name);
    }

    public function test_history_page_requests_phone_number_without_whatsapp_otp(): void
    {
        $this->get(route('history.index'))
            ->assertSee('Nomor HP lengkap')
            ->assertSee('4 digit terakhir nomor HP')
            ->assertDontSee('OTP');
    }

    public function test_customer_sees_all_orders_for_phone_after_entering_full_number_and_last_four_digits(): void
    {
        $firstOrder = Order::factory()->create([
            'code' => '3DP-FIRST1234',
            'customer_phone' => '6281234567890',
        ]);
        $secondOrder = Order::factory()->create([
            'code' => '3DP-SECOND123',
            'customer_phone' => '6281234567890',
        ]);
        $otherPhoneOrder = Order::factory()->create([
            'code' => '3DP-OTHER1234',
            'customer_phone' => '6281234567000',
        ]);

        $this->post(route('history.verify'), [
            'phone' => '081234567890',
            'phone_last_four' => '7890',
        ])->assertRedirect(route('history.index'))
            ->assertSessionHas('verified_phone', '6281234567890');

        $this->get(route('history.index'))
            ->assertSee($firstOrder->code)
            ->assertSee($secondOrder->code)
            ->assertDontSee($otherPhoneOrder->code);
        $this->get(route('tracking.show', $secondOrder->code))->assertSee($secondOrder->customer_name);
    }

    public function test_customer_cannot_view_order_history_with_incorrect_last_four_phone_digits(): void
    {
        Order::factory()->create(['customer_phone' => '6281234567890']);

        $this->from(route('history.index'))->post(route('history.verify'), [
            'phone' => '081234567890',
            'phone_last_four' => '1234',
        ])->assertSessionHasErrors(['phone_last_four' => 'Nomor HP atau 4 digit terakhir tidak cocok.'])
            ->assertSessionMissing('verified_phone');
    }

    public function test_customer_cannot_track_order_with_incorrect_last_four_phone_digits(): void
    {
        $order = Order::factory()->create(['customer_phone' => '6281234567890']);

        $this->from(route('tracking.lookup'))->post(route('tracking.verify'), [
            'code' => $order->code,
            'phone_last_four' => '1234',
        ])->assertSessionHasErrors(['phone_last_four' => 'Kode transaksi atau 4 digit terakhir nomor HP tidak cocok.'])
            ->assertSessionMissing('tracked_order_code');

        $this->get(route('tracking.show', $order->code))->assertDontSee($order->customer_name);
    }

    public function test_customer_cannot_access_another_order_from_verified_tracking_session(): void
    {
        $verifiedOrder = Order::factory()->create(['customer_phone' => '6281234567890']);
        $otherOrder = Order::factory()->create(['customer_name' => 'Other Customer']);

        $this->withSession(['tracked_order_code' => $verifiedOrder->code])
            ->get(route('tracking.show', $otherOrder->code))
            ->assertSee('4 digit terakhir nomor HP')
            ->assertDontSee('Other Customer');
    }

    public function test_cancelling_ready_order_restores_stock_once(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'ready',
            'stock' => 1,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'fulfillment_type' => 'ready',
            'quantity' => 2,
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ])->assertRedirect();

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_admin_can_upload_stl_model_for_product_preview(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $color = Color::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Test Sculpture',
            'description' => 'Test model.',
            'price' => 89000,
            'is_active' => 1,
            'customization_type' => 'standard',
            'model_file' => UploadedFile::fake()->createWithContent('sculpture.stl', "solid test\nendsolid test\n"),
            'variants' => [
                $color->id => ['color_id' => $color->id, 'availability' => 'ready', 'stock' => 2, 'lead_days' => 14],
            ],
        ]);

        $product = Product::query()->where('name', 'Test Sculpture')->firstOrFail();
        $response->assertRedirect(route('admin.products.index'));
        $this->assertSame('stl', $product->model_format);
        $this->assertNotNull($product->model_path);
        $this->assertTrue(Storage::disk('local')->exists($product->model_path));
        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-3d-viewer', false)
            ->assertSee('data-model-format="stl"', false);
        $this->get(route('products.model', $product->slug))->assertOk();
    }

    public function test_clicker_checkout_uses_product_variant_stock_for_selected_component_colors(): void
    {
        $product = Product::factory()->create([
            'customization_type' => 'clicker',
            'name_max_length' => 8,
            'price' => 135000,
            'model_path' => 'models/keycap-template.3mf',
            'model_format' => '3mf',
        ]);
        $baseColor = Color::factory()->create(['name' => 'Base Olive']);
        $buttonColor = Color::factory()->create(['name' => 'Button Cream']);
        $nameColor = Color::factory()->create(['name' => 'Name Coral']);
        $baseVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $baseColor->id,
            'availability' => 'ready',
            'stock' => 5,
            'lead_days' => 14,
        ]);
        $buttonVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $buttonColor->id,
            'availability' => 'po',
            'stock' => 0,
            'lead_days' => 13,
        ]);
        $nameVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $nameColor->id,
            'availability' => 'ready',
            'stock' => 4,
            'lead_days' => 14,
        ]);
        ProductComponentVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $baseColor->id,
            'component' => 'base',
        ]);
        ProductComponentVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $buttonColor->id,
            'component' => 'button',
        ]);
        ProductComponentVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $nameColor->id,
            'component' => 'name',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-keycap-generator="true"', false)
            ->assertSee('data-keycap-template-url="'.route('products.model', $product->slug).'"', false)
            ->assertSee('Warna base')
            ->assertSee('Nama pada tombol')
            ->assertSee('maxlength="8"', false);
        $this->get(route('checkout.custom.create', [
            'product' => $product->slug,
            'customization' => [
                'base_color_id' => $baseColor->id,
                'button_color_id' => $buttonColor->id,
                'name_color_id' => $nameColor->id,
                'name' => 'NADIA',
            ],
        ]))->assertOk()->assertSee('NADIA')->assertSee('Warna tulisan')->assertSee('max="4"', false);

        $response = $this->post(route('checkout.custom.store', $product->slug), [
            'customer_name' => 'Nadia Putri',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati 12, Bandung',
            'quantity' => 2,
            'customization' => [
                'name' => 'NADIA',
                'base_color_id' => $baseColor->id,
                'button_color_id' => $buttonColor->id,
                'name_color_id' => $nameColor->id,
            ],
        ]);

        $order = Order::query()->with('items')->firstOrFail();
        $item = $order->items->firstOrFail();
        $response->assertRedirect(route('orders.confirmation', $order->code));
        $this->assertSame(3, $baseVariant->fresh()->stock);
        $this->assertSame(0, $buttonVariant->fresh()->stock);
        $this->assertSame(2, $nameVariant->fresh()->stock);
        $this->assertSame('po', $item->fulfillment_type);
        $this->assertSame(13, $item->lead_days);
        $this->assertSame('NADIA', $item->customization['name']);
        $this->assertSame(5, $item->customization['name_length']);
        $this->assertSame(135000, $item->unit_price);
        $this->assertSame(270000, $item->subtotal);
        $this->assertSame('Base Olive', $item->customization['components']['base']['color_name']);
        $this->assertSame('Button Cream', $item->customization['components']['button']['color_name']);
        $this->get(route('orders.confirmation', $order->code))->assertOk()->assertSee('NADIA')->assertSee('Button Cream');
        $this->post(route('tracking.verify'), [
            'code' => $order->code,
            'phone_last_four' => '7890',
        ])->assertRedirect(route('tracking.show', $order->code));
        $this->get(route('tracking.show', $order->code))->assertOk()->assertSee('NADIA')->assertSee('Base Olive');

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Nama emboss: “NADIA”')->assertSee('Name Coral');
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ])->assertRedirect();

        $this->assertSame(5, $baseVariant->fresh()->stock);
        $this->assertSame(4, $nameVariant->fresh()->stock);
    }

    public function test_clicker_checkout_reserves_a_shared_color_variant_only_once(): void
    {
        $product = Product::factory()->create(['customization_type' => 'clicker']);
        $color = Color::factory()->create(['name' => 'Olive']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'ready',
            'stock' => 5,
            'lead_days' => 14,
        ]);

        foreach (['base', 'button', 'name'] as $component) {
            ProductComponentVariant::factory()->create([
                'product_id' => $product->id,
                'color_id' => $color->id,
                'component' => $component,
            ]);
        }

        $response = $this->post(route('checkout.custom.store', $product->slug), [
            'customer_name' => 'Nadia Putri',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati 12, Bandung',
            'quantity' => 2,
            'customization' => [
                'name' => 'NADIA',
                'base_color_id' => $color->id,
                'button_color_id' => $color->id,
                'name_color_id' => $color->id,
            ],
        ]);

        $this->assertSame(3, $variant->fresh()->stock);
        $response->assertRedirect(route('orders.confirmation', Order::query()->firstOrFail()->code));
    }

    public function test_clicker_checkout_charges_extra_for_each_character_after_four(): void
    {
        $product = Product::factory()->create([
            'customization_type' => 'clicker',
            'price' => 25000,
            'additional_character_price' => 7000,
        ]);
        $color = Color::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'availability' => 'po',
            'stock' => 0,
        ]);

        foreach (['base', 'button', 'name'] as $component) {
            ProductComponentVariant::factory()->create([
                'product_id' => $product->id,
                'color_id' => $color->id,
                'component' => $component,
            ]);
        }

        $this->get(route('products.show', $product->slug))
            ->assertSee('Rp 25.000')
            ->assertSee('Rp 7.000 per huruf');
        $this->get(route('checkout.custom.create', [
            'product' => $product->slug,
            'customization' => [
                'base_color_id' => $color->id,
                'button_color_id' => $color->id,
                'name_color_id' => $color->id,
                'name' => 'ABCDE',
            ],
        ]))->assertSee('Rp 32.000')->assertSee('data-unit-price="32000"', false);

        foreach ([['ABCD', 1], ['ABCDE', 2]] as [$name, $quantity]) {
            $this->post(route('checkout.custom.store', $product->slug), [
                'customer_name' => 'Nadia Putri',
                'customer_phone' => '081234567890',
                'shipping_address' => 'Jl. Melati 12, Bandung',
                'quantity' => $quantity,
                'customization' => [
                    'name' => $name,
                    'base_color_id' => $color->id,
                    'button_color_id' => $color->id,
                    'name_color_id' => $color->id,
                ],
            ])->assertRedirect();
        }

        $items = OrderItem::query()->orderBy('id')->get();

        $this->assertSame(25000, $items[0]->unit_price);
        $this->assertSame(25000, $items[0]->subtotal);
        $this->assertSame(32000, $items[1]->unit_price);
        $this->assertSame(64000, $items[1]->subtotal);
    }

    public function test_clicker_name_cannot_exceed_product_character_limit(): void
    {
        $product = Product::factory()->create(['customization_type' => 'clicker', 'name_max_length' => 4]);
        $this->from(route('checkout.custom.create', ['product' => $product->slug]))
            ->post(route('checkout.custom.store', $product->slug), [
                'customer_name' => 'Nadia Putri',
                'customer_phone' => '081234567890',
                'shipping_address' => 'Jl. Melati 12, Bandung',
                'quantity' => 1,
                'customization' => [
                    'name' => 'TOOLONG',
                    'base_color_id' => 1,
                    'button_color_id' => 2,
                    'name_color_id' => 3,
                ],
            ])
            ->assertSessionHasErrors('customization.name');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_clicker_name_rejects_spaces_and_punctuation(): void
    {
        $product = Product::factory()->create(['customization_type' => 'clicker', 'name_max_length' => 10]);

        $this->post(route('checkout.custom.store', $product->slug), [
            'customer_name' => 'Nadia Putri',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati 12, Bandung',
            'quantity' => 1,
            'customization' => [
                'name' => 'NA-DIA',
                'base_color_id' => 1,
                'button_color_id' => 2,
                'name_color_id' => 3,
            ],
        ])->assertSessionHasErrors('customization.name');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_cannot_set_clicker_name_length_above_ten(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Studio Clicker',
            'price' => 99000,
            'is_active' => 1,
            'customization_type' => 'clicker',
            'name_max_length' => 11,
        ])->assertSessionHasErrors('name_max_length');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_configure_clicker_components_and_name_limit(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $colors = Color::factory()->count(3)->create();
        $components = [];
        $variants = [];

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertSee('Pilihan warna komponen')
            ->assertSee('base')
            ->assertSee('button')
            ->assertSee('huruf')
            ->assertSee('name="name_max_length" min="1" max="10"', false)
            ->assertSee('type="checkbox"', false)
            ->assertDontSee('Tipe stok Base')
            ->assertDontSee('Estimasi PO Base');

        foreach (['base', 'button', 'name'] as $component) {
            foreach ($colors as $color) {
                $components[$component][$color->id] = [
                    'component' => $component,
                    'color_id' => $color->id,
                    'is_active' => 1,
                ];
                $variants[$color->id] = [
                    'color_id' => $color->id,
                    'availability' => 'ready',
                    'stock' => 5,
                    'lead_days' => 14,
                ];
            }
        }

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Studio Clicker',
            'description' => 'Clicker personalisasi.',
            'price' => 99000,
            'additional_character_price' => 7000,
            'is_active' => 1,
            'customization_type' => 'clicker',
            'name_max_length' => 7,
            'variants' => $variants,
            'component_variants' => $components,
        ]);

        $product = Product::query()->where('name', 'Studio Clicker')->firstOrFail();
        $response->assertRedirect(route('admin.products.index'));
        $this->assertSame('clicker', $product->customization_type);
        $this->assertSame(7, $product->name_max_length);
        $this->assertSame(7000, $product->additional_character_price);
        $this->assertCount(9, $product->componentVariants()->where('is_active', true)->get());
        $this->get(route('products.show', $product->slug))->assertOk()->assertSee('Nama pada tombol');
    }
}
