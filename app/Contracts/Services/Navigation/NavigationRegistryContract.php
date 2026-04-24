<?php

declare(strict_types=1);

namespace App\Contracts\Services\Navigation;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;

interface NavigationRegistryContract
{
    /**
     * @return list<PillarSubNavProviderContract>
     */
    public function providers(): array;

    public function providerFor(Pillar $pillar): ?PillarSubNavProviderContract;

    /**
     * @return array<string, mixed>
     */
    public function pillarsPayload(User $user): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function subNavForPillar(Pillar $pillar, User $user): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryCardsForPillar(Pillar $pillar, User $user): array;

    public function currentPillar(?string $path): ?Pillar;
}
