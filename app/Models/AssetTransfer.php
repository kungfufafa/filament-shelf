<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetTransferDocumentType;
use App\Enums\NbhStatus;
use Database\Factories\AssetTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'business_entity_id',
    'letter_number',
    'from_user_id',
    'to_user_id',
    'document',
    'transfer_date',
])]
class AssetTransfer extends Model
{
    /** @use HasFactory<AssetTransferFactory> */
    use HasFactory;

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssetTransferDetail::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Generate nomor BA berdasarkan format business entity, melanjutkan sequence.
     *
     * @param  int|string|null  $newNumber
     */
    public static function generateLetterNumber(?BusinessEntity $businessEntity, $newNumber = null): string
    {
        if (! $businessEntity) {
            return '';
        }

        $format = $businessEntity->format;
        if (empty($format) || $format === '0' || $format === '-') {
            $name = preg_replace('/^(pt\.|pt|cv\.|cv)\s+/i', '', trim($businessEntity->name));
            $words = preg_split('/[\s\-\.]+/', $name);
            if (count($words) === 1) {
                $prefix = strtoupper(substr($words[0], 0, 6));
            } else {
                $initials = '';
                foreach ($words as $word) {
                    $cleanedWord = preg_replace('/[^a-zA-Z0-9]/', '', $word);
                    if ($cleanedWord !== '') {
                        $initials .= substr($cleanedWord, 0, 1);
                    }
                }
                $prefix = strtoupper($initials);
            }
            $format = $prefix.'/';
        }

        if ($newNumber === null) {
            $lastTransfer = self::where('business_entity_id', $businessEntity->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $lastNumber = 0;
            if ($lastTransfer && str_starts_with($lastTransfer->letter_number, $format)) {
                $lastNumber = (int) preg_replace('/\D/', '', substr($lastTransfer->letter_number, -6));
            }

            do {
                $lastNumber++;
                $newNumberStr = str_pad((string) $lastNumber, 6, '0', STR_PAD_LEFT);
                $candidate = "{$format}{$newNumberStr}";
                $exists = self::where('letter_number', $candidate)->exists();
            } while ($exists);

            return $candidate;
        }

        return "{$format}{$newNumber}";
    }

    /**
     * Scope transfers whose sender holds the general_affair role. Uses the
     * string `role` column on the users table (replaces spatie/laravel-permission).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeGeneralAffair($query)
    {
        return $query->whereHas('fromUser', function (Builder $q): void {
            $q->where('role', 'general_affair');
        });
    }

    public function documentType(): ?AssetTransferDocumentType
    {
        $this->loadMissing(['fromUser', 'toUser']);

        return AssetTransferDocumentType::fromUsers($this->fromUser, $this->toUser);
    }

    public function documentCode(): string
    {
        return $this->documentType()?->code() ?? 'UNKNOWN';
    }

    public function documentColor(): string
    {
        return $this->documentType()?->color() ?? 'gray';
    }

    public function getStatusAttribute(): string
    {
        return $this->documentType()?->label() ?? 'Status Transfer Tidak Valid';
    }

    public function scopeForDocumentType(Builder $query, AssetTransferDocumentType|string|null $type): Builder
    {
        if (is_string($type)) {
            $type = AssetTransferDocumentType::tryFrom($type);
        }

        if (! $type) {
            return $query;
        }

        // Filter by the users.role column instead of spatie's roles pivot.
        $hasGeneralAffairRole = fn (Builder $roleQuery): Builder => $roleQuery->where('role', 'general_affair');

        return match ($type) {
            AssetTransferDocumentType::SerahTerima => $query
                ->whereHas('fromUser', $hasGeneralAffairRole)
                ->whereDoesntHave('toUser', $hasGeneralAffairRole),
            AssetTransferDocumentType::PengalihanBarang => $query
                ->whereDoesntHave('fromUser', $hasGeneralAffairRole)
                ->whereDoesntHave('toUser', $hasGeneralAffairRole),
            AssetTransferDocumentType::PengembalianBarang => $query
                ->whereDoesntHave('fromUser', $hasGeneralAffairRole)
                ->whereHas('toUser', $hasGeneralAffairRole),
        };
    }

    public function applyLifecycleToAssets(?AssetRequest $sourceAssetRequest = null): void
    {
        $documentType = $this->documentType();

        if (! $documentType) {
            throw new RuntimeException('Alur transfer aset tidak valid untuk kombinasi pemberi dan penerima ini.');
        }

        $this->loadMissing('details.asset.recipient');
        $this->ensureAssetsCanMove($documentType, $sourceAssetRequest);

        foreach ($this->details as $detail) {
            $asset = $detail->asset;

            if (! $asset) {
                continue;
            }

            $asset->recipient_id = $this->to_user_id;
            $asset->recipient_business_entity_id = $this->business_entity_id;
            $asset->condition_status = $this->conditionAfterTransfer($documentType);

            if ($asset->condition_status instanceof AssetCondition && $asset->condition_status->isTransferable()) {
                $asset->nbh_status = NbhStatus::None;
                $asset->nbh_responsible_user_id = null;
            }

            $asset->save();
        }
    }

    protected function ensureAssetsCanMove(AssetTransferDocumentType $documentType, ?AssetRequest $sourceAssetRequest = null): void
    {
        if ($this->details->isEmpty()) {
            throw new RuntimeException('BA transfer wajib memiliki minimal satu aset.');
        }

        $assetIds = $this->details
            ->pluck('asset_id')
            ->filter()
            ->map(fn ($assetId): int => (int) $assetId)
            ->values();

        if ($assetIds->count() !== $assetIds->unique()->count()) {
            throw new RuntimeException('Aset dalam BA transfer tidak boleh duplikat.');
        }

        foreach ($this->details as $detail) {
            $asset = $detail->asset;

            if (! $detail->asset_id || ! $asset) {
                throw new RuntimeException('Detail BA transfer memuat aset yang tidak ditemukan.');
            }

            if ($this->assetAlreadyReflectsTransfer($asset, $documentType)) {
                continue;
            }

            if (! ($asset->condition_status instanceof AssetCondition) || ! $asset->condition_status->isTransferable()) {
                throw new RuntimeException(sprintf(
                    'Aset "%s" tidak bisa ditransfer karena statusnya %s.',
                    $asset->name,
                    $asset->condition_status_label,
                ));
            }

            if ($asset->hasOpenAssetRequestLock($sourceAssetRequest?->id)) {
                throw new RuntimeException(sprintf(
                    'Aset "%s" sedang dalam pengajuan aktif dan belum bisa ditransfer.',
                    $asset->name,
                ));
            }

            if ($documentType === AssetTransferDocumentType::SerahTerima) {
                $this->ensureAssetCanBeDispatchedFromGeneralAffair($asset);

                continue;
            }

            if ((int) $asset->recipient_id !== (int) $this->from_user_id) {
                throw new RuntimeException(sprintf(
                    'Aset "%s" bukan milik pemberi transfer.',
                    $asset->name,
                ));
            }
        }
    }

    protected function assetAlreadyReflectsTransfer(Asset $asset, AssetTransferDocumentType $documentType): bool
    {
        return (int) $asset->recipient_id === (int) $this->to_user_id
            && (int) ($asset->recipient_business_entity_id ?? 0) === (int) ($this->business_entity_id ?? 0)
            && $asset->condition_status === $this->conditionAfterTransfer($documentType);
    }

    protected function conditionAfterTransfer(AssetTransferDocumentType $documentType): AssetCondition
    {
        return $documentType->returnsToGeneralAffair()
            ? AssetCondition::Available
            : AssetCondition::Transferred;
    }

    protected function ensureAssetCanBeDispatchedFromGeneralAffair(Asset $asset): void
    {
        if ($asset->condition_status !== AssetCondition::Available) {
            throw new RuntimeException(sprintf(
                'Aset "%s" harus berstatus Tersedia sebelum diserahterimakan dari General Affairs.',
                $asset->name,
            ));
        }

        if ($asset->recipient_id && ! Asset::userHasGeneralAffairRole($asset->recipient)) {
            throw new RuntimeException(sprintf(
                'Aset "%s" masih tercatat pada pemegang non-General Affairs.',
                $asset->name,
            ));
        }
    }
}
