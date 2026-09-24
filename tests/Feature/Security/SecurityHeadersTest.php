<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function test_web_responses_carry_security_headers(): void
    {
        $csp = $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }

    public function test_api_responses_carry_security_headers(): void
    {
        $this->getJson('/api/v1/me')->assertHeader('X-Frame-Options', 'DENY');
    }
}
