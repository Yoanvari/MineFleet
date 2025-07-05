<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    protected $fillable = [
        'vehicle_id', 'service_date', 'description', 'cost',
        'next_service_date', 'next_service_odometer'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
