<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthzTest extends TestCase
{
    public function test_healthz_returns_ok(): void
    {
        $response = $this->get('/healthz');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}
