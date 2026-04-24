<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class CommunicationPillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::COMMUNICATION;
    }

    public function subNav(User $user): array
    {
        return [
            new PillarSubNavItem(__('nav.communication.postal'), route('communication.postal.index'), 'EnvelopeOpenIcon'),
            new PillarSubNavItem(__('nav.communication.chat'), route('communication.chat'), 'ChatBubbleLeftRightIcon'),
            new PillarSubNavItem(__('nav.communication.calendar'), route('communication.calendar.index'), 'CalendarIcon'),
        ];
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.communication.summary.inbox'), (string) $user->notifications()->whereNull('read_at')->count(), null, null, route('communication.postal.index'), 'InboxIcon'),
        ];
    }
}
