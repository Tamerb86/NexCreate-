<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_web_health_endpoint_responds(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson(['status' => 'healthy']);
    }

    public function test_api_health_endpoint_responds(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'healthy']);
    }

    public function test_api_root_responds(): void
    {
        $this->getJson('/api')
            ->assertOk()
            ->assertJson(['api' => 'NexCreate API']);
    }
}
