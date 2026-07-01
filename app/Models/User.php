<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (! $user->is_admin) {
                return;
            }

            if (auth()->id() === $user->getKey()) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot delete the account you are currently using.',
                ]);
            }

            if (static::query()->where('is_admin', true)->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'The final admin account cannot be deleted.',
                ]);
            }
        });
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }
}
