<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class CheckInOut extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by',
        'checked_out_by',
    ];

    /**
     * Automatically cast date fields to Carbon instances
     */
    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

        /**
     * Spatie activity log settings
     */
    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->useLogName('check_in_out') // change based on model
        ->logOnlyDirty();       // logs only changed fields
}


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
     * Relationship: User who checked the vehicle in
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Relationship: User who checked the vehicle out
     */
    public function checkedOutBy()
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    /**
     * Optional: Scope to filter only currently checked-in vehicles
     */
    public function scopeCurrentlyCheckedIn($query)
    {
        return $query->whereNull('checked_out_at');
    }

    public function checkedInByUser()
{
    return $this->belongsTo(User::class, 'checked_in_by');
}

public function checkedOutByUser()
{
    return $this->belongsTo(User::class, 'checked_out_by');
}

}
