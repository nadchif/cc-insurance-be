<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $entries = DB::select('select * from entities ORDER BY name', [
            1
        ]);
        // The array we're going to return
        $orgs = [];
        foreach ($entries as $entity){
            $orgs[$entity->code] = $entity->id;
        }
        $file = fopen(__DIR__ . "/members.csv", "r");

        while (!feof($file)) {
            $entryData = (fgetcsv($file));

            if (is_array($entryData)) {
                $entry_email = $entryData[1];
                $entry_first_name = $entryData[2];
                $entry_last_name = $entryData[3];
                $entry_password = $entryData[5];
                $entry_category = $entryData[7];
                $entry_code = $entryData[20];
                $entry_email_verified_at = $entryData[8];
                $entry_phone = $entryData[9];
                $entry_address = $entryData[10];
                $entry_org = $orgs['none'];
                if(isset($orgs[$entry_code])){
                    $entry_org = $orgs[$entry_code];
                }else{
                    echo "Invalid org for: ". $entry_email . " (".$entry_code.") \r\n";
                    $entry_org = $orgs['none'];
                }

                DB::table('users')->insert([
                    'first_name' => $entry_first_name,
                    'last_name' => $entry_last_name,
                    'email' => $entry_email,
                    'password' => $entry_password,
                    'category' => $entry_category,
                    'email_verified_at' => $entry_email_verified_at,
                    'phone' => $entry_phone,
                    'address' => $entry_address,
                    'entity' => $entry_org,
                ]);
            }
        }
        DB::table('users')->insert([
            'first_name' => 'Default',
            'last_name' => 'User',
            'email' => 'user@domain.com',
            'password' => password_hash(time(), PASSWORD_DEFAULT),
            'category' => 'user',
            'phone' => '',
            'address' => '',
            'email_verified_at' => Date('Y-m-d H:i:s'),
            'entity' => $orgs['none'],
        ]);
    }
}
