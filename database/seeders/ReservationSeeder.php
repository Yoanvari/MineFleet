<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\{Reservation, ReservationApproval, Vehicle, Driver, User, Location};

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId     = User::where('role', 'admin')->first()->id;
        $approverIds = User::where('role', 'approver')->pluck('id');
        $vehicleIds  = Vehicle::pluck('id');
        $driverIds   = Driver::pluck('id');
        $locationIds = Location::where('type', 'mine_site')->pluck('id');

        foreach (range(1, 30) as $i) {
            $start = Carbon::now()->subDays(rand(0, 30))->setTime(rand(6, 10), 0);
            $end   = (clone $start)->addHours(rand(2, 10));

            $reservation = Reservation::create([
                'reservation_code' => strtoupper(Str::random(8)),
                'vehicle_id'       => $vehicleIds->random(),
                'driver_id'        => $driverIds->random(),
                'requester_id'     => $adminId,        // pemesanan selalu via admin pool
                'destination_id'   => $locationIds->random(),
                'start_datetime'   => $start,
                'end_datetime'     => $end,
                'purpose'          => "Perjalanan dinas {$i}",
                'status'           => 'pending',
            ]);

            // Buat 2 level approver
            foreach ([1, 2] as $level) {
                ReservationApproval::create([
                    'reservation_id' => $reservation->id,
                    'approver_id'    => $approverIds->random(),
                    'level'          => $level,
                ]);
            }
        }
    }
}
