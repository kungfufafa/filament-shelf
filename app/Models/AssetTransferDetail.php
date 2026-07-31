<?php

namespace App\Models;

use Database\Factories\AssetTransferDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_transfer_id',
    'asset_id',
    'equipment',
])]
class AssetTransferDetail extends Model
{
    /** @use HasFactory<AssetTransferDetailFactory> */
    use HasFactory;

    public function assetTransfer(): BelongsTo
    {
        return $this->belongsTo(AssetTransfer::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
