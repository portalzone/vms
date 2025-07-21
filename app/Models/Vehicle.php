<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturer',
        'model',
        'year',
        'plate_number',
        'created_by',
        'updated_by'
    ];

    /**
     * Relationship: One Vehicle has One Driver (inverse of Driver::vehicle())
     */
    public function driver()
    {
        return $this->hasOne(Driver::class);
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


    
    
}
