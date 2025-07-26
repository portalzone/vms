<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Trip extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'driver_id',     // This is user_id
        'vehicle_id',
        'start_location',
        'end_location',
        'start_time',
        'end_time',
    ];

    /**
     * Spatie activity log settings
     */
    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->useLogName('trip') // change based on model
        ->logOnlyDirty();       // logs only changed fields
}

    public function driver()
{
    return $this->belongsTo(Driver::class);
}

public function vehicle()
{
    return $this->belongsTo(Vehicle::class);
}

}
