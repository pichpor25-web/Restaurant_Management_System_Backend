<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:make-admin {email : The email address of the existing user}')]
#[Description('Promote an existing user to an administrator')]
class MakeUserAdmin extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user found with that email address.');

            return self::FAILURE;
        }

        if (! $user->is_active) {
            $this->error('The user account is inactive. Activate it before promoting it.');

            return self::FAILURE;
        }

        if ($user->role === 'admin') {
            $this->info('The user is already an administrator.');

            return self::SUCCESS;
        }

        $user->update(['role' => 'admin']);

        $this->info('User promoted to administrator successfully.');

        return self::SUCCESS;
    }
}
