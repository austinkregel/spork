<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model implements Crud
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'location',
        'description',
        'acquired_at',
        'order_id',
        'shipped_at',
        'delivered_at',
        'returned_at',
        'return_tracking_number',
        'tracking_number',
        'status',
        'condition',
        'meta',
    ];

    protected $casts = [
        'acquired_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function booted()
    {
        static::creating(function ($asset) {
            $asset->uuid = (string) \Illuminate\Support\Str::uuid();

            if (auth()->check()) {
                $asset->owner_id = auth()->id();
                $asset->owner_type = get_class(auth()->user());
            }
        });
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->dontSubmitEmptyLogs()
            ->logOnly(['name', 'type', 'location', 'description', 'acquired_at', 'order_id', 'shipped_at', 'delivered_at', 'returned_at', 'return_tracking_number', 'tracking_number', 'status', 'condition', 'meta']);
    }
}
