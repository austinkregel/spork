<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\PasswordValidationRules;
use PHPUnit\Framework\TestCase;

class PasswordValidationRulesTest extends TestCase
{
    use PasswordValidationRules;

    public function test_password_rules(): void
    {
        $rules = $this->passwordRules();

        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('confirmed', $rules);
        $this->assertInstanceOf(\Laravel\Fortify\Rules\Password::class, $rules[2]);
    }
}
