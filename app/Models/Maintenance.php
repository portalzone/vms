<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'description',
        'status',
        'cost',
        'date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'cost' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function expense()
    {
        return $this->hasOne(Expense::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
