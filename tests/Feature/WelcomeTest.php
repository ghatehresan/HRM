<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class WelcomeTest extends TestCase
{
    public function test_home_page_renders_rtl_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="fa" dir="rtl">', false);
        $response->assertSee('قطعه‌رسان');
        $response->assertSee('منابع انسانی');
    }

    public function test_health_endpoint_reports_status(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk();
        $response->assertJsonStructure(['app', 'env', 'php', 'laravel', 'db', 'time_utc']);
        $response->assertJson(['db' => 'ok']);
    }
}
