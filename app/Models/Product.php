<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'price', 'image_path', 'model_path', 'model_format', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_active' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
