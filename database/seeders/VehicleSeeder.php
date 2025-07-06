<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $passengerNames = [
            'Toyota Avanza', 'Honda Mobilio', 'Suzuki Ertiga', 'Mitsubishi Xpander', 'Daihatsu Terios',
            'Nissan Livina', 'Hyundai Stargazer', 'Kia Carens', 'Wuling Confero', 'Chevrolet Spin'
        ];

        foreach (range(1, 10) as $i) {
            Vehicle::create([
                'name'         => Arr::random($passengerNames),
                'license_plate'=> fake()->bothify('B #### ??'),
                'type'         => 'passenger',
                'ownership'    => $i <= 6 ? 'owned' : 'rented',
                'status'       => 'available',
            ]);
        }

        $cargoNames = [
            'Isuzu Elf', 'Mitsubishi Fuso', 'Toyota Dyna', 'Hino Dutro', 'Suzuki Carry'
        ];

        foreach (range(1, 10) as $i) {
            Vehicle::create([
                'name'         => Arr::random($cargoNames),
                'license_plate'=> fake()->bothify('L #### ??'),
                'type'         => 'cargo',
                'ownership'    => $i <= 3 ? 'owned' : 'rented',
                'status'       => 'available',
            ]);
        }
    }
}
