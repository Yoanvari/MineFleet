<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\{ServiceRecord, Vehicle};
use Illuminate\Database\Seeder;

class ServiceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Vehicle::pluck('id') as $vehicleId) {
            ServiceRecord::create([
                'vehicle_id'            => $vehicleId,
                'service_date'          => Carbon::now()->subDays(rand(30, 180)),
                'description'           => 'Servis berkala',
                'cost'                  => rand(500_000, 3_000_000),
                'next_service_date'     => Carbon::now()->addDays(rand(30, 180)),
                'next_service_odometer' => rand(15_000, 80_000),
            ]);
        }
    }
}
