<?php

namespace App\Models;

use Database\Factories\DivisionApproverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'division_id',
    'user_id',
    'level',
])]
class DivisionApprover extends Model
{
    /** @use HasFactory<DivisionApproverFactory> */
    use HasFactory;

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
