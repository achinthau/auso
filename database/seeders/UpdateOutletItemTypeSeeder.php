<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateOutletItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // First, update the specific records where contact_no is '010' or '011'
        DB::table('outlets')->whereIn('contact_no', ['010', '011'])
                            ->update(['outlet_item_type' => 'new']);

        // Then, update all other records to set outlet_item_type to 'old'
        DB::table('outlets')->whereNotIn('contact_no', ['010', '011'])
                            ->update(['outlet_item_type' => 'old']);
    }
}
