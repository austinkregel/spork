<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model implements Crud, ModelQuery
{
    use HasFactory;

    public $fillable = [
        'name',
        'type',
        'status',
        'checklist',
        'notes',
        'start_date',
        'end_date',
        'service_identifier',
    ];

    protected $casts = [
        'checklist' => 'json',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
