<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Vehicle extends Model
{
    use HasFactory, LogsActivity;

protected $fillable = [
    'manufacturer',
    'model',
    'year',
    'plate_number',
    'ownership_type',
    'owner_id',
    'created_by',
    'updated_by',
];


    /**
     * Spatie activity log settings
     */
    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->useLogName('vehicle') // change based on model
        ->logOnlyDirty();       // logs only changed fields
}

    /**
     * Relationship: One Vehicle has One Driver (inverse of Driver::vehicle())
     */
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function drivers()
{
    return $this->hasMany(Driver::class);
}


public function trips()
{
    return $this->hasMany(Trip::class);
}

// User that created and edited a vehicle
public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function editor()
{
    return $this->belongsTo(User::class, 'updated_by');
}

    

    

    /**
     * Vehicle has many check-in/out records
     */
    public function checkins()
    {
        return $this->hasMany(CheckInOut::class);
    }

    /**
     * Vehicle has many expenses
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Vehicle has many maintenance records
     */
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
    
public function maintenanceRecords()
{
    return $this->hasMany(\App\Models\Maintenance::class);
}

// vehicle owner

public function owner()
{
    return $this->belongsTo(User::class, 'owner_id');
}


    
    
}
