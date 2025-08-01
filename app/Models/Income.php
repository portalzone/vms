<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Income extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'trip_id',
        'source',
        'amount',
        'description',
        'date',
    ];
    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->useLogName('income') // change based on model
        ->logOnlyDirty();       // logs only changed fields
}
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    
}
