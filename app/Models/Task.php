<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'business_entity_id',
    'work_timestamp',
    'name',
    'description',
    'vendor_id',
    'cost',
    'location',
    'status',
    'attachment',
    'document_upload',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Auto-generate the human-readable task code on create and whenever the
     * owning business entity changes.
     */
    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            $task->code = static::generateTaskCode($task);
        });

        static::updating(function (Task $task): void {
            if ($task->isDirty('business_entity_id')) {
                $task->code = static::generateTaskCode($task);
            }
        });
    }

    protected static function generateTaskCode(Task $task): string
    {
        $year = now()->year;

        $businessEntityCode = strtoupper((string) $task->businessEntity->name);

        $lastTaskForYear = Task::where('business_entity_id', $task->business_entity_id)
            ->whereYear('created_at', $year)
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTaskForYear) {
            $lastOrder = intval(explode('/', $lastTaskForYear->code)[0]);
            $nextOrder = $lastOrder + 1;
        } else {
            $nextOrder = 1;
        }

        return sprintf('%03d/BAP/%s/GA/%s', $nextOrder, $businessEntityCode, $year);
    }
}
