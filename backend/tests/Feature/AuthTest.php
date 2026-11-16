<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_customer_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Customer',
            'email' => 'john.cust@example.com',
            'phone' => '+15551234567',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.cust@example.com',
            'status' => 'active',
        ]);

        $user = User::where('email', 'john.cust@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_customer_cannot_register_with_existing_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_customer_cannot_login_with_wrong_password(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_customer_can_manage_addresses(): void
    {
        $user = User::create([
            'name' => 'Address Tester',
            'email' => 'addr@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);
        $token = $user->createToken('test')->plainTextToken;

        // Create Address
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/addresses', [
                'type' => 'shipping',
                'first_name' => 'Address',
                'last_name' => 'Tester',
                'street' => '123 Fashion Blvd',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
                'country' => 'US',
                'is_default' => true,
            ]);

        $createRes->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'street' => '123 Fashion Blvd',
        ]);
    }

    public function test_inactive_customer_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'inactive',
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_can_logout_and_revokes_token(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_customer_cannot_elevate_role_via_profile_update(): void
    {
        $user = User::create([
            'name' => 'Sneaky Customer',
            'email' => 'sneaky@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Sneaky Admin Attempt',
                'role' => 'super_admin',
                'roles' => ['super_admin'],
                'role_id' => 1,
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->hasRole('super_admin'));
    }
}
