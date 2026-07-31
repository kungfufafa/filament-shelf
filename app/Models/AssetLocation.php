<?php

namespace App\Models;

use Database\Factories\AssetLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'address', 'description'])]
class AssetLocation extends Model
{
    /** @use HasFactory<AssetLocationFactory> */
    use HasFactory;
}
