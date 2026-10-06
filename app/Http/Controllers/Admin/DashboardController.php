<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'products' => Product::query()->where('is_active', true)->count(),
            'orders' => Order::query()->count(),
            'pending' => Order::query()->where('status', 'pending_payment')->count(),
            'revenue' => Order::query()->where('payment_status', 'confirmed')->sum('subtotal'),
        ];
        $recentOrders = Order::query()->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
