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
        $this->assertContains('finance_tracking', $keys);
        $this->assertContains('communication_hub', $keys);
        $this->assertContains('automation_ops', $keys);
        $this->assertContains('infrastructure_monitoring', $keys);
        $this->assertContains('content_research', $keys);
        $this->assertContains('personal_crm', $keys);
        $this->assertContains('home_ops', $keys);

        $this->assertCount(8, $keys);

        $first = $templates[0];
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('description', $first);
        $this->assertArrayHasKey('sections', $first);
        $this->assertArrayHasKey('preferred_resource_types', $first);
    }
}
