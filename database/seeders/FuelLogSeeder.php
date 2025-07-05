<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\{FuelLog, Vehicle, User};
use Illuminate\Database\Seeder;

class FuelLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles  = Vehicle::pluck('id');
        $recorder  = User::where('role', 'admin')->first()->id;

        foreach ($vehicles as $vehicleId) {
            foreach (range(1, 6) as $i) {
                FuelLog::create([
                    'vehicle_id'  => $vehicleId,
                    'log_date'    => Carbon::now()->subDays(rand(1, 60)),
                    'odometer'    => rand(10_000, 60_000),
                    'litres'      => rand(20, 60),
                    'cost'        => rand(300_000, 1_500_000),
                    'recorded_by' => $recorder,
                ]);
            }
        }
    }
}
