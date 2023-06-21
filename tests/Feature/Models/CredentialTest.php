<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;

class CredentialTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
