<?php

declare(strict_types=1);

namespace App\Models;

enum ProjectMembershipRole: string
{
    case OWNER = 'owner';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::OWNER, self::EDITOR => true,
            self::VIEWER => false,
        };
    }

    public function canManageMembers(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
