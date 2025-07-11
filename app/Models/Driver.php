<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'home_address',
        'sex',
        'driver_licence_number',
        'vehicle_id', // foreign key
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
