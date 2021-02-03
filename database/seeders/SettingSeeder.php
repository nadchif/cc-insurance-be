<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::create([
            'receiving_email' => 'dchifsolutions@gmail.com',
            'user_signup' => 1,
            'monthly_backup' => 1,
        ]);
    }
}
