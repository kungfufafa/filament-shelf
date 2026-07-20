<?php

namespace App\Models;

use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name'])]
class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory, SoftDeletes;

    public function approvers(): HasMany
    {
        return $this->hasMany(DivisionApprover::class)->orderBy('level', 'asc');
    }
}
