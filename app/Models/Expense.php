<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'amount',
        'description',
        'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
