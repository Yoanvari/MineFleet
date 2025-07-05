<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'type', 'region'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'destination');
    }
}
