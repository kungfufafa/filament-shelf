<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookReceiverTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_valid_webhook_payload()
    {
        config(['services.core.webhook_secret' => 'secret123']);

        $payload = [
            'action' => 'created',
            'employee' => [
                'id' => 99,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '0812345',
                'status' => 'active',
            ],
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'secret123');

        $response = $this->postJson('/api/webhooks/employees', $payload, [
            'X-Signature' => $signature,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('employees', [
            'id' => 99,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_rejects_invalid_signature()
    {
        config(['services.core.webhook_secret' => 'secret123']);

        $payload = [
            'action' => 'created',
            'employee' => [
                'id' => 99,
                'name' => 'John Doe',
                'status' => 'active',
            ],
        ];

        $response = $this->postJson('/api/webhooks/employees', $payload, [
            'X-Signature' => 'wrong-signature',
        ]);

        $response->assertStatus(401);
    }
}
