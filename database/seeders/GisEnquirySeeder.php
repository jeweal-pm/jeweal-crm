<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GisEnquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
  
        \App\Models\GisEnquiry::factory()->create([
            'first_name' => 'Born',
            'last_name' => 'Cha',
            'email' => 'Born@jeweal.com',
            'phone_number' => '+6699558781',
            'inquiry' => 'request_quotation',
            'status' => 'pending',
            'message' => 'I need to test GIS',
        ]);
    }
}
