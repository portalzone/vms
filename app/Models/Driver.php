<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

   protected $fillable = [
    'user_id',
    'vehicle_id',
    'license_number', // ✅ correct key
    'phone_number',
    'home_address',
    'sex',
];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
