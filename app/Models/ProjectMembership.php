<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMembership extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => ProjectMembershipRole::class,
            'permissions' => 'array',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->declined_at === null;
    }

    public function canEdit(): bool
    {
        return $this->role instanceof ProjectMembershipRole && $this->role->canEdit();
    }

    public function canManageMembers(): bool
    {
        return $this->role instanceof ProjectMembershipRole && $this->role->canManageMembers();
    }
}
