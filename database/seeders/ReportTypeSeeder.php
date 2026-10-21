<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Police Check',
            ],
            [
                'name' => 'Accident',
            ],
            [
                'name' => 'Traffic Jam',
            ],
        ];

        // Insert users into the database
        foreach ($types as $type) {
            DB::table('report_types')->insert($type);
        }
    }
}
