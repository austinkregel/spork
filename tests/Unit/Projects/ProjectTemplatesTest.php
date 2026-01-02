<?php

declare(strict_types=1);

namespace Tests\Unit\Projects;

use App\Projects\ProjectTemplates;
use Tests\TestCase;

class ProjectTemplatesTest extends TestCase
{
    public function test_it_exposes_frontend_templates(): void
    {
        $templates = $this->app->make(ProjectTemplates::class)->forFrontend();

        $this->assertIsArray($templates);
        $this->assertNotEmpty($templates);

        $keys = collect($templates)->pluck('key')->all();
        $this->assertContains('custom', $keys);
        $this->assertContains('infra_deployment', $keys);
        $this->assertContains('research_hub', $keys);
        $this->assertContains('personal_upkeep', $keys);

        $first = $templates[0];
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('description', $first);
        $this->assertArrayHasKey('sections', $first);
        $this->assertArrayHasKey('preferred_resource_types', $first);
    }
}


