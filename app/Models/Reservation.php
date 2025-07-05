<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_code', 'vehicle_id', 'driver_id', 'requester_id', 'destination',
        'start_datetime', 'end_datetime', 'purpose', 'status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'destination');
    }

    public function approvals()
    {
        return $this->hasMany(ReservationApproval::class);
    }
}
