<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'checked_in_at',
        'checked_out_at',
    ];

    /**
     * Automatically cast date fields to Carbon instances
     */
    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    /**
     * Relationship: Each check-in/out belongs to a vehicle
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Relationship: Each check-in/out belongs to a driver
     */
    public function driver()
{
    return $this->belongsTo(Driver::class)->with('user');
}


    /**
     * Optional: Scope to filter only currently checked-in vehicles
     */
    public function scopeCurrentlyCheckedIn($query)
    {
        return $query->whereNull('checked_out_at');
    }
}
