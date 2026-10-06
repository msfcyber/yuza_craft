<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneOtp extends Model
{
    protected $fillable = ['phone', 'code_hash', 'attempts', 'expires_at', 'verified_at'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'expires_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
