<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\VehicleChecksheetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'asset_id',
    'reference_number',
    'pic',
    'license_plate',
    'location',
    'destination',
    'remarks',
    'start_km',
    'departure_time',
    'departure_photo',
    'departure_damage_report',
    'end_km',
    'return_time',
    'return_photo',
    'return_damage_report',
    'rental_duration',
    'distance_traveled',
])]
class VehicleChecksheet extends Model
{
    /** @use HasFactory<VehicleChecksheetFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'return_time' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    protected static function booted(): void
    {
        static::saving(function (VehicleChecksheet $vehicleChecksheet): void {
            $vehicleChecksheet->calculateRentalDetails();
        });

        static::deleting(function (VehicleChecksheet $vehicleChecksheet): void {
            $vehicleChecksheet->deleteRelatedFiles();
        });

        static::updating(function (VehicleChecksheet $vehicleChecksheet): void {
            $vehicleChecksheet->deleteOldFiles();
        });
    }

    protected function calculateRentalDetails(): void
    {
        if (isset($this->start_km, $this->end_km) && is_numeric($this->start_km) && is_numeric($this->end_km)) {
            $this->distance_traveled = max(0, $this->end_km - $this->start_km);
        } else {
            $this->distance_traveled = 0;
        }

        if (isset($this->departure_time, $this->return_time)) {
            $departure = Carbon::parse($this->departure_time);
            $return = Carbon::parse($this->return_time);

            $durationInMinutes = $departure->diffInMinutes($return);

            $this->rental_duration = round($durationInMinutes / 1440, 5);
        } else {
            $this->rental_duration = 0;
        }
    }

    protected function deleteRelatedFiles(): void
    {
        if ($this->departure_photo) {
            Storage::disk('public')->delete($this->departure_photo);
        }

        if ($this->departure_damage_report) {
            Storage::disk('public')->delete($this->departure_damage_report);
        }

        if ($this->return_photo) {
            Storage::disk('public')->delete($this->return_photo);
        }

        if ($this->return_damage_report) {
            Storage::disk('public')->delete($this->return_damage_report);
        }
    }

    protected function deleteOldFiles(): void
    {
        if ($this->isDirty('departure_photo') && $this->getOriginal('departure_photo')) {
            Storage::disk('public')->delete($this->getOriginal('departure_photo'));
        }

        if ($this->isDirty('departure_damage_report') && $this->getOriginal('departure_damage_report')) {
            Storage::disk('public')->delete($this->getOriginal('departure_damage_report'));
        }

        if ($this->isDirty('return_photo') && $this->getOriginal('return_photo')) {
            Storage::disk('public')->delete($this->getOriginal('return_photo'));
        }

        if ($this->isDirty('return_damage_report') && $this->getOriginal('return_damage_report')) {
            Storage::disk('public')->delete($this->getOriginal('return_damage_report'));
        }
    }
}
