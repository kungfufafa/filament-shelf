<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'username',
    'email',
    'whatsapp_number',
    'password',
    'email_verified_at',
    'business_entity_id',
    'job_title_id',
    'role',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        ];
    }

    /**
     * Role-based access check replacing spatie/laravel-permission. Compares
     * the nullable `role` column on the users table against the given role
     * name (e.g. "super_admin", "general_affair").
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Normalize email on set: trim surrounding whitespace, reject line breaks,
     * and store empty input as null to keep the column nullable-unique.
     */
    public function setEmailAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['email'] = null;

            return;
        }

        $email = trim((string) $value);

        if (preg_match('/[\r\n]/', $email) === 1) {
            throw new InvalidArgumentException('Email must not contain line breaks.');
        }

        $this->attributes['email'] = $email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return filled($this->role);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * Display name with job title suffix, e.g. "Budi (Manager)".
     */
    public function nameWithJobTitle(): string
    {
        $this->loadMissing('jobTitle');

        $title = $this->jobTitle?->title;

        return filled($title) ? "{$this->name} ({$title})" : $this->name;
    }

    public function assetTransfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function assetTransfersFrom(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'from_user_id');
    }

    public function assetTransfersTo(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'to_user_id');
    }

    public function assetTransferDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            AssetTransferDetail::class, // final target model
            AssetTransfer::class, // intermediate model
            'from_user_id', // foreign key on AssetTransfer (link to User)
            'asset_transfer_id', // foreign key on AssetTransferDetail (link to AssetTransfer)
            'id', // local key on User
            'id' // local key on AssetTransfer
        );
    }

    /**
     * Whether another user shares the same name (used for duplicate detection).
     */
    public function isDuplicate(): bool
    {
        return User::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->exists();
    }
}
