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
    ];

    // Relationship: One Vehicle has One Driver (optional)
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    // Optional: Expenses, CheckIns, Maintenance
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function checkins()
    {
        return $this->hasMany(CheckInOut::class);
    }
}
