<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\ShortCode\ShortCodeCreated;
use App\Events\Models\ShortCode\ShortCodeCreating;
use App\Events\Models\ShortCode\ShortCodeDeleted;
use App\Events\Models\ShortCode\ShortCodeDeleting;
use App\Events\Models\ShortCode\ShortCodeUpdated;
use App\Events\Models\ShortCode\ShortCodeUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortCode extends Model
{
    use HasFactory;

    public $fillable = [
        'short_code',
        'long_url',
        'is_enabled',
        'status',
    ];

    public $dispatchesEvents = [
        'created' => ShortCodeCreated::class,
        'creating' => ShortCodeCreating::class,
        'deleting' => ShortCodeDeleting::class,
        'deleted' => ShortCodeDeleted::class,
        'updating' => ShortCodeUpdating::class,
        'updated' => ShortCodeUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'bool',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generate(string $link)
    {
    }
}
