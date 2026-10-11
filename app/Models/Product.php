<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const CLICKER_INCLUDED_CHARACTER_COUNT = 4;

    protected $fillable = ['name', 'slug', 'description', 'price', 'additional_character_price', 'image_path', 'model_path', 'model_format', 'is_active', 'customization_type', 'name_max_length'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'additional_character_price' => 'integer', 'is_active' => 'boolean', 'name_max_length' => 'integer'];
    }

    public function clickerUnitPriceForName(string $name): int
    {
        $additionalCharacterCount = max(0, mb_strlen($name) - self::CLICKER_INCLUDED_CHARACTER_COUNT);

        return $this->price + ($additionalCharacterCount * $this->additional_character_price);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function componentVariants(): HasMany
    {
        return $this->hasMany(ProductComponentVariant::class);
    }
}
