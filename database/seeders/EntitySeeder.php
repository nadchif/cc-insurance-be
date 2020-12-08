<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntitySeeder extends Seeder
{
 /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $file = fopen(__DIR__ . "/churches.csv", "r");

        while (! feof($file)) {
            $churchData = (fgetcsv($file));
            if (is_array($churchData)) {
                $church_name = $churchData[1];
                $church_address = $churchData[2];
                $church_suncode = $churchData[6];

                DB::table('entities')->insert([
                    'name' => $church_name,
                    'address' => $church_address,
                    'code' => $church_suncode
                ]);
            }
        }
        DB::table('entities')->insert([
            'name' => 'Default entity',
            'address' => 'RSA',
            'code' => 'none'
        ]);
    }
}
