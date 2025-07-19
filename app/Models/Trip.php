<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',       // This references the user_id of the driver
        'vehicle_id',      // This references the vehicle used
        'start_location',
        'end_location',
        'start_time',
        'end_time',
    ];

    /**
     * Get the vehicle associated with the trip.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver (User) associated with the trip.
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
