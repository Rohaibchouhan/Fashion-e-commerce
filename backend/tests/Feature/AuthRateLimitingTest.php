<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_login_rate_limiting_rejects_excessive_attempts(): void
    {
        // Limit is 10 attempts per minute
        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson('/api/v1/auth/login', [
                'email' => 'victim@example.com',
                'password' => 'WrongPassword!',
            ]);
            // Either 422 (invalid credentials) or already rate limited
            $this->assertTrue(in_array($res->getStatusCode(), [422, 429]));
        }

        // The 11th attempt MUST be blocked by throttle with 429 Too Many Requests
        $excessResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'victim@example.com',
            'password' => 'WrongPassword!',
        ]);

        $excessResponse->assertStatus(429);
    }

    public function test_forgot_password_rate_limiting_rejects_excessive_attempts(): void
    {
        // Limit is 5 attempts per minute
        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'rate.limit.test@example.com',
            ]);
            $this->assertTrue(in_array($res->getStatusCode(), [200, 429]));
        }

        // The 6th attempt MUST return 429
        $excessResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'rate.limit.test@example.com',
        ]);

        $excessResponse->assertStatus(429);
    }

    public function test_reset_password_rate_limiting_rejects_excessive_attempts(): void
    {
        // Limit is 5 attempts per minute
        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'dummy-token',
                'email' => 'rate.limit.reset@example.com',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);
            $this->assertTrue(in_array($res->getStatusCode(), [400, 429]));
        }

        // The 6th attempt MUST return 429
        $excessResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'dummy-token',
            'email' => 'rate.limit.reset@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $excessResponse->assertStatus(429);
    }

    public function test_registration_rate_limiting_rejects_excessive_attempts(): void
    {
        // Limit is 10 attempts per minute
        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson('/api/v1/auth/register', [
                'name' => "Bot User {$i}",
                'email' => "bot{$i}@example.com",
                'password' => 'Password123!',
            ]);
            $this->assertTrue(in_array($res->getStatusCode(), [201, 422, 429]));
        }

        // The 11th attempt MUST return 429
        $excessResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Bot User 11',
            'email' => 'bot11@example.com',
            'password' => 'Password123!',
        ]);

        $excessResponse->assertStatus(429);
    }

    public function test_admin_login_rate_limiting_rejects_excessive_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $res = $this->post('/admin/login', [
                'email' => 'admin@wearday.com',
                'password' => 'WrongAdminPass!',
            ]);
            $this->assertTrue(in_array($res->getStatusCode(), [302, 429]));
        }

        $excessResponse = $this->post('/admin/login', [
            'email' => 'admin@wearday.com',
            'password' => 'WrongAdminPass!',
        ]);

        $excessResponse->assertStatus(429);
    }
}
