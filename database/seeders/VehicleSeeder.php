<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 10 mobil penumpang
        foreach (range(1, 10) as $i) {
            Vehicle::create([
                'name'         => "Passenger Car {$i}",
                'license_plate'=> fake()->bothify('B #### ??'),
                'type'         => 'passenger',
                'ownership'    => $i <= 6 ? 'owned' : 'rented',
                'status'       => 'available',
            ]);
        }

        // 5 truk kargo
        foreach (range(1, 5) as $i) {
            Vehicle::create([
                'name'         => "Cargo Truck {$i}",
                'license_plate'=> fake()->bothify('L #### ??'),
                'type'         => 'cargo',
                'ownership'    => $i <= 3 ? 'owned' : 'rented',
                'status'       => 'available',
            ]);
        }
    }
}
