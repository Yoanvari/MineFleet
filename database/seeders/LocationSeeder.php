<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1 kantor pusat
        Location::create([
            'name'   => 'Head Office',
            'type'   => 'head_office',
        ]);

        // 1 kantor cabang
        Location::create([
            'name'   => 'Branch Office',
            'type'   => 'branch_office',
        ]);

        // 6 tambang
        foreach (range(1, 6) as $i) {
            Location::create([
                'name'   => "Mine Site {$i}",
                'type'   => 'mine_site',
            ]);
        }
    }
}
