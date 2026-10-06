<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\WhatsAppOtpSender;
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

    public function test_customer_must_verify_phone_before_viewing_order_history(): void
    {
        $order = Order::factory()->create(['customer_phone' => '6281234567890']);
        $sentCode = null;
        $this->mock(WhatsAppOtpSender::class)->shouldReceive('send')
            ->once()
            ->withArgs(function (string $phone, string $code) use (&$sentCode): bool {
                $sentCode = $code;

                return $phone === '6281234567890';
            });

        $this->get(route('history.index'))->assertDontSee($order->code);
        $this->post(route('history.send-otp'), ['phone' => '081234567890'])->assertSessionHas('otp_sent');
        $this->post(route('history.verify-otp'), ['phone' => '081234567890', 'code' => $sentCode])
            ->assertRedirect(route('history.index'))
            ->assertSessionHas('verified_phone', '6281234567890');

        $this->get(route('history.index'))->assertSee($order->code);
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
}
