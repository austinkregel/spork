<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Tags;

use App\Models\Email;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\Tags\TaggableClassDiscoveryService;
use Tests\TestCase;

class TaggableClassDiscoveryServiceTest extends TestCase
{
    public function test_it_discovers_only_models_implementing_app_taggable(): void
    {
        /** @var TaggableClassDiscoveryService $service */
        $service = app(TaggableClassDiscoveryService::class);
        $service->forgetCache();

        $classes = $service->discover(useCache: false);

        $this->assertContains(Transaction::class, $classes);
        $this->assertContains(User::class, $classes);
        $this->assertNotContains(Email::class, $classes);
    }
}
