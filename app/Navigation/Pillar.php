<?php

declare(strict_types=1);

namespace App\Navigation;

enum Pillar: string
{
    case FINANCE = 'finance';
    case COMMUNICATION = 'communication';
    case FEEDS = 'feeds';
    case PROJECTS = 'projects';
    case AUTOMATIONS = 'automations';
    case INFRASTRUCTURE = 'infrastructure';

    public function label(): string
    {
        return match ($this) {
            self::FINANCE => __('nav.pillars.finance'),
            self::COMMUNICATION => __('nav.pillars.communication'),
            self::FEEDS => __('nav.pillars.feeds'),
            self::PROJECTS => __('nav.pillars.projects'),
            self::AUTOMATIONS => __('nav.pillars.automations'),
            self::INFRASTRUCTURE => __('nav.pillars.infrastructure'),
        };
    }

    /** Heroicons v2 outline name (used by DynamicIcon / GlassRail). */
    public function icon(): string
    {
        return match ($this) {
            self::FINANCE => 'BanknotesIcon',
            self::COMMUNICATION => 'ChatBubbleLeftRightIcon',
            self::FEEDS => 'RssIcon',
            self::PROJECTS => 'ClipboardDocumentListIcon',
            self::AUTOMATIONS => 'BoltIcon',
            self::INFRASTRUCTURE => 'ServerStackIcon',
        };
    }

    /** URL segment after `/-/`, e.g. `finance` -> `/-/finance`. */
    public function slug(): string
    {
        return $this->value;
    }

    public function summaryCardsTtlSeconds(): int
    {
        return match ($this) {
            self::FINANCE => 300,
            self::INFRASTRUCTURE => 30,
            default => 60,
        };
    }

    public static function tryFromPath(string $path): ?self
    {
        $path = trim($path, '/');
        $parts = explode('/', $path);
        if (count($parts) < 2 || $parts[0] !== '-') {
            return null;
        }

        return self::tryFrom($parts[1]);
    }
}
