<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $entries = DB::select('select * from entities ORDER BY name', [
            1,
        ]);
        // The array we're going to return
        $orgs = [];
        foreach ($entries as $entity) {
            $orgs[$entity->name] = $entity->id;
        }

        $file = fopen(__DIR__ . "/allentries.csv", "r");

        while (!feof($file)) {
            $data = (fgetcsv($file));
            //  ["id","dateinsured","entity","erf","address","type","description","serial","value1617","value1718","account","fnCT","ORGID"]
            if (is_array($data)) {
                $set_date = $data[1] == '' ? '2000-01-01' : $data[1];
                $date_insured = Carbon::createFromFormat('Y-m-d',  $set_date)->format('Y-m-d H:i:s');
                
                $entity_name = $data[2];
                $entity_org = "";
                if (isset($orgs[$entity_name])) {
                    $entity_org = $orgs[$entity_name];
                } else {
                    echo "Invalid org for: " . $date_insured . " (" . $entity_name . ") \r\n";
                    $entity_org = $orgs[0];
                }
                
                $erf = $data[3];
                $address = $data[4];
                $insurance_type = $data[5];
                $description = $data[6];
                $serial = $data[7];
                $building_value = \floatval($data[8]);
                $contents_value = \floatval($data[9]);
                     DB::table('entries')->insert([
                        'date_insured' => $date_insured,
                        'entity' => $entity_org,
                        'erf' => $erf,
                        'description'=>$description,
                        'address'=> $address,
                        'type'=>$insurance_type,
                        'serial'=>$serial,
                        'building_value'=>$building_value,
                        'contents_value'=>$contents_value,
                        
                    ]);
       
            }
        }}
}
