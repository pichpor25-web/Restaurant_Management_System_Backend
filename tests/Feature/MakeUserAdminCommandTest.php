<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeUserAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_promotes_existing_active_user_to_admin(): void
    {
        $user = User::factory()->create();

        $this->artisan('users:make-admin', ['email' => $user->email])
            ->expectsOutput('User promoted to administrator successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_command_fails_when_no_user_has_the_email(): void
    {
        $this->artisan('users:make-admin', ['email' => 'missing@example.test'])
            ->expectsOutput('No user found with that email address.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_does_not_promote_an_inactive_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->artisan('users:make-admin', ['email' => $user->email])
            ->expectsOutput('The user account is inactive. Activate it before promoting it.')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'customer',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_login_and_receives_the_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin');
    }
}
