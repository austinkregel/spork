<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PostLoginRedirectTest extends TestCase
{
	public function test_redirects_to_intended_after_login(): void
	{
		$this->withSession([
			'url.intended' => 'http://spork.localhost/-/projects',
		]);

		$this->actingAsUser();

		$response = $this->get('http://spork.localhost/post-login');

		$response->assertRedirect('http://spork.localhost/-/projects');
	}

	public function test_redirects_to_home_when_no_intended(): void
	{
		$this->actingAsUser();

		$response = $this->get('http://spork.localhost/post-login');

		$response->assertRedirect('http://spork.localhost/-/dashboard');
	}
}



