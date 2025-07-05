<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (range(1, 10) as $i) {
            Driver::create([
                'name'           => "Driver {$i}",
                'license_number' => sprintf('SIM-%04d', $i),
                'phone'          => '08' . fake()->numerify('##########'),
                'is_available'   => true,
            ]);
        }
    }
}
