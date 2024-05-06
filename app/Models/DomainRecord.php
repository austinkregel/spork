<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\DomainRecord\DomainRecordCreated;
use App\Events\Models\DomainRecord\DomainRecordCreating;
use App\Events\Models\DomainRecord\DomainRecordDeleted;
use App\Events\Models\DomainRecord\DomainRecordDeleting;
use App\Events\Models\DomainRecord\DomainRecordUpdated;
use App\Events\Models\DomainRecord\DomainRecordUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DomainRecord extends Model implements Crud, ModelQuery
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'ttl',
        'content',
        'comment',
        'tags',
        'value',
        'timeout',
    ];

    public $dispatchesEvents = [
        'created' => DomainRecordCreated::class,
        'creating' => DomainRecordCreating::class,
        'deleting' => DomainRecordDeleting::class,
        'deleted' => DomainRecordDeleted::class,
        'updating' => DomainRecordUpdating::class,
        'updated' => DomainRecordUpdated::class,
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('domain-record');
    }
}
