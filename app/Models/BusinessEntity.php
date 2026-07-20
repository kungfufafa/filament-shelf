<?php

namespace App\Models;

use Database\Factories\BusinessEntityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'format', 'color', 'letterhead'])]
class BusinessEntity extends Model
{
    /** @use HasFactory<BusinessEntityFactory> */
    use HasFactory;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetTransfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
