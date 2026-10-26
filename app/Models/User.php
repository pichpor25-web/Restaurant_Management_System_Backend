<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// ១. បន្ថែម 'avatar' ចូលទៅក្នុង Fillable
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'is_active', 'avatar'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    public const CREATED_AT = null;

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * បន្ថែម Attribute ដើម្បីឱ្យវាផ្ញើ avatar_url មកជាមួយផង
     */
    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * ២. បង្កើត Function សម្រាប់បង្កើត URL ពេញលេញ
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }
}