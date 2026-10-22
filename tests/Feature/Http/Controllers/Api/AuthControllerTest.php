<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_customer_and_returns_a_working_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Alex Example',
            'email' => 'alex@example.test',
            'phone' => '5550101234',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'role' => 'admin',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful')
            ->assertJsonPath('user.email', 'alex@example.test')
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonPath('user.is_active', true);
        $this->assertArrayNotHasKey('password', $response->json('user'));

        $this->assertDatabaseHas('users', [
            'email' => 'alex@example.test',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $user = User::query()->where('email', 'alex@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.email', 'alex@example.test');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Alex Example',
            'email' => 'alex@example.test',
            'phone' => '5550101234',
            'password' => 'secret-pass-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.email', $user->email);

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_inactive_users(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_is_rate_limited_after_five_attempts_for_the_same_email_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'alex@example.test',
                'password' => 'incorrect-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/login', [
            'email' => 'alex@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainTextToken = $token->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);

        Auth::forgetGuards();

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/user')
            ->assertUnauthorized();
    }

    public function test_api_resources_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/categories')
            ->assertUnauthorized();

        $this->getJson('/api/v1/tables')
            ->assertUnauthorized();

        $this->getJson('/api/v1/inventory')
            ->assertUnauthorized();
    }
}
