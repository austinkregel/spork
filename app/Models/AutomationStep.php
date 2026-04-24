<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationStep extends Model implements ModelQuery
{
    use HasFactory;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'automation_id',
        'order',
        'type',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'int',
            'config' => 'array',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
