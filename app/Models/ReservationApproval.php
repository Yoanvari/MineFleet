<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationApproval extends Model
{
    protected $fillable = [
        'reservation_id', 'approver_id', 'level', 'status', 'comments', 'approved_at'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
