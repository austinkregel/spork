<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Research extends Model
{
    use HasFactory;

    public $casts = [
        'sources' => 'json',
    ];

    public $fillable = [
        'topic',
        'notes',
        'sources',
    ];

    public function projects(): MorphToMany
    {
        return $this->morphToMany(
            Project::class,
            'resource',
            'project_resources'
        );
    }
}
