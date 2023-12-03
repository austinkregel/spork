<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShortCode extends Model
{
    use HasFactory;

    public $fillable = [
        'short_code',
        'long_url',
        'is_enabled',
        'status',
    ];

    public $casts = [
        'is_enabled' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generate(string $link)
    {
    }
}
