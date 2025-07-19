<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',     // This is user_id
        'vehicle_id',
        'start_location',
        'end_location',
        'start_time',
        'end_time',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // driver_id points directly to users.id
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
