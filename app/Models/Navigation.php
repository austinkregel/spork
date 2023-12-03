<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model implements Crud
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'authentication_required' => 'boolean',
        'settings' => 'json'
    ];

    public function children()
    {
        return $this->hasMany(Navigation::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Navigation::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
