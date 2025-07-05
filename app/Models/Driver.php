<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'license_number', 'phone', 'is_available'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
