<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();

        $this->user = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => Hash::make('OldPassword123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->user->roles()->sync([$customerRole->id]);
    }

    public function test_cannot_reset_password_with_arbitrary_or_unverified_token(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'attacker-arbitrary-token-12345',
            'email' => 'jane@example.com',
            'password' => 'HackedPassword123!',
            'password_confirmation' => 'HackedPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This password reset token is invalid or has expired.',
            ]);

        // Password must remain untouched
        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
        $this->assertFalse(Hash::check('HackedPassword123!', $this->user->password));
    }

    public function test_successful_password_reset_with_valid_token(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password has been reset successfully. You may now log in.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $this->user->password));
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $token = Password::broker()->createToken($this->user);

        // 1. First reset succeeds
        $firstResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'FirstNewPassword123!',
            'password_confirmation' => 'FirstNewPassword123!',
        ]);
        $firstResponse->assertStatus(200);

        // 2. Second reset using identical token MUST fail
        $secondResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'SecondNewPassword123!',
            'password_confirmation' => 'SecondNewPassword123!',
        ]);

        $secondResponse->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This password reset token is invalid or has expired.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('FirstNewPassword123!', $this->user->password));
        $this->assertFalse(Hash::check('SecondNewPassword123!', $this->user->password));
    }

    public function test_cannot_reset_password_with_expired_token(): void
    {
        $token = Password::broker()->createToken($this->user);

        // Fast forward time past expiration window (60 minutes configured in config/auth.php)
        $this->travel(65)->minutes();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'ExpiredPassword123!',
            'password_confirmation' => 'ExpiredPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This password reset token is invalid or has expired.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
    }

    public function test_cannot_reset_password_for_wrong_email(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $otherUser = User::create([
            'name' => 'Victim User',
            'email' => 'victim@example.com',
            'password' => Hash::make('VictimPass123!'),
            'status' => 'active',
        ]);
        $otherUser->roles()->sync([$customerRole->id]);

        // Token created for Jane
        $token = Password::broker()->createToken($this->user);

        // Attacker attempts to use Jane's token for Victim's email
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'victim@example.com',
            'password' => 'HackedVictim123!',
            'password_confirmation' => 'HackedVictim123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This password reset token is invalid or has expired.',
            ]);

        $otherUser->refresh();
        $this->assertTrue(Hash::check('VictimPass123!', $otherUser->password));
    }

    public function test_cannot_reset_password_with_confirmation_mismatch(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'NewSecretPassword123!',
            'password_confirmation' => 'MismatchConfirmation999!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
    }

    public function test_existing_sanctum_tokens_are_invalidated_after_password_reset(): void
    {
        // Issue active personal access token to user
        $sanctumToken = $this->user->createToken('active-session')->plainTextToken;

        // Verify active token works initially
        $preReset = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->getJson('/api/v1/auth/me');
        $preReset->assertStatus(200);

        // Perform legitimate password reset
        $resetToken = Password::broker()->createToken($this->user);
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'jane@example.com',
            'password' => 'SecuredPass123!',
            'password_confirmation' => 'SecuredPass123!',
        ]);
        $resetResponse->assertStatus(200);

        // Tokens in database must be completely revoked
        $this->assertEquals(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->user->id)->count());

        // Reset auth guards in the test runner container to force fresh token verification
        app('auth')->forgetGuards();
        $this->flushSession();

        // Old Sanctum token MUST be revoked and unusable
        $postReset = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->getJson('/api/v1/auth/me');
        $postReset->assertStatus(401);
    }

    public function test_cannot_reset_password_when_confirmation_field_is_missing(): void
    {
        $token = Password::broker()->createToken($this->user);

        // Attempt password reset without providing password_confirmation
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'NewSecretPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password_confirmation']);

        // Assert user's password remained unchanged
        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
        $this->assertFalse(Hash::check('NewSecretPassword123!', $this->user->password));
    }

    public function test_nonexistent_email_and_invalid_token_return_identical_error_responses(): void
    {
        // Case 1: Existing user with an invalid token
        $responseInvalidToken = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'completely-invalid-token-12345',
            'email' => 'jane@example.com',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        // Case 2: Non-existent email with dummy token
        $responseNonexistentEmail = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'completely-invalid-token-12345',
            'email' => 'nonexistent_account_audit@example.com',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        // 1. Both return HTTP 400 Bad Request
        $this->assertSame(400, $responseInvalidToken->status());
        $this->assertSame(400, $responseNonexistentEmail->status());

        // 2. Both return byte-for-byte identical JSON payload to prevent email enumeration
        $this->assertSame($responseInvalidToken->json(), $responseNonexistentEmail->json());
        $responseInvalidToken->assertJson([
            'success' => false,
            'message' => 'This password reset token is invalid or has expired.',
        ]);
    }

    public function test_authenticated_browser_session_is_invalidated_after_password_reset(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $admin = User::create([
            'name' => 'Admin Session Test',
            'email' => 'admin_session@example.com',
            'password' => Hash::make('AdminInitialPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->sync([$superAdminRole->id]);

        // 1. Authenticate user in a browser session via standard web login
        $loginResponse = $this->post('/admin/login', [
            'email' => 'admin_session@example.com',
            'password' => 'AdminInitialPass123!',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard'));

        // 2. Verify active session can successfully access protected web dashboard
        $initialAccess = $this->get('/admin');
        $initialAccess->assertStatus(200);

        // 3. User performs a password reset via API
        $resetToken = Password::broker()->createToken($admin);
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'admin_session@example.com',
            'password' => 'NewAdminSecuredPass123!',
            'password_confirmation' => 'NewAdminSecuredPass123!',
        ]);
        $resetResponse->assertStatus(200);

        // 4. Forget auth guards to simulate the subsequent browser HTTP request from the user's browser
        app('auth')->forgetGuards();

        // 5. Subsequent request using the old session MUST be rejected and redirected to admin.login
        $subsequentAccess = $this->get('/admin');
        $subsequentAccess->assertRedirect(route('admin.login'));
        $this->assertGuest('web');
    }

    public function test_forgot_password_dispatches_instructions_safely(): void
    {
        // 1. Existing user
        $resReal = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'jane@example.com',
        ]);
        $resReal->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset instructions have been dispatched to jane@example.com.',
            ]);

        // Token record should exist in database
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'jane@example.com',
        ]);

        // 2. Non-existent user returns consistent response without error
        $resFake = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);
        $resFake->assertStatus(200);
    }
}
