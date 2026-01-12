<?php

declare(strict_types=1);

namespace Tests\Unit\Projects;

use App\Models\Domain;
use App\Services\Projects\ProjectResourceRegistry;
use Tests\TestCase;

class ProjectResourceRegistryTest extends TestCase
{
    public function test_it_exposes_allowed_resource_types(): void
    {
        $registry = $this->app->make(ProjectResourceRegistry::class);

        $this->assertContains(Domain::class, $registry->allowedResourceTypes());
        $this->assertTrue($registry->isAllowed(Domain::class));
        $this->assertFalse($registry->isAllowed(\App\Models\ThreadParticipant::class));
    }

    public function test_it_builds_frontend_metadata(): void
    {
        $registry = $this->app->make(ProjectResourceRegistry::class);

        $data = $registry->forFrontend();

        $this->assertArrayHasKey('groups', $data);
        $this->assertArrayHasKey('resources', $data);
        $this->assertIsArray($data['groups']);
        $this->assertIsArray($data['resources']);

        $domainMeta = collect($data['resources'])->firstWhere('type', Domain::class);
        $this->assertNotNull($domainMeta);
        $this->assertSame(Domain::class, $domainMeta['type']);
        $this->assertNotEmpty($domainMeta['label']);
        $this->assertNotEmpty($domainMeta['group']);
    }
}
