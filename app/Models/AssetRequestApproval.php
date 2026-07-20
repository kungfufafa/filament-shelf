<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\AssetRequestApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'asset_request_id',
    'user_id',
    'public_token',
    'level',
    'status',
    'notes',
    'decided_by_user_id',
    'decided_at',
])]
class AssetRequestApproval extends Model
{
    /** @use HasFactory<AssetRequestApprovalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssetRequestApproval $approval): void {
            if (! $approval->public_token) {
                $approval->public_token = self::generatePublicToken();
            }
        });
    }

    public static function generatePublicToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('public_token', $token)->exists());

        return $token;
    }

    public function ensurePublicToken(): string
    {
        if (blank($this->public_token)) {
            $this->public_token = self::generatePublicToken();

            if ($this->exists) {
                $this->saveQuietly();
            }
        }

        return (string) $this->public_token;
    }

    public function publicApprovalUrl(): string
    {
        return route('public.asset-requests.approval', $this->ensurePublicToken());
    }

    public function assetRequest(): BelongsTo
    {
        return $this->belongsTo(AssetRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
