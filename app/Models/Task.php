<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
