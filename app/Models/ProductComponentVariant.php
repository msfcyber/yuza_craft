<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComponentVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'color_id', 'component', 'availability', 'stock', 'lead_days', 'is_active'];

    protected function casts(): array
    {
        return ['stock' => 'integer', 'lead_days' => 'integer', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function getComponentLabelAttribute(): string
    {
        return ['base' => 'Base', 'button' => 'Tombol', 'name' => 'Warna nama'][$this->component] ?? ucfirst($this->component);
    }
}
