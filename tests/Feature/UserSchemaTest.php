<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_the_requested_fields_and_can_be_created(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'name',
            'email',
            'password',
            'role',
            'phone',
            'is_active',
            'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumns('users', [
            'email_verified_at',
            'remember_token',
            'created_at',
        ]));

        $user = User::factory()->create();

        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->updated_at);
    }
}
