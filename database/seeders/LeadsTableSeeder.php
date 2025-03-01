<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('leads')->truncate();

        // Define possible statuses and sources
        $statuses = ['pending', 'active', 'inactive'];
        $sources = ['website', 'referral', 'ad', 'email'];
        
        // Generate 100 sample leads
        for ($i = 1; $i <= 100; $i++) {
            Lead::create([
                'name' => "Lead $i",
                'email' => "lead{$i}@example.com",
                'phone' => '123-456-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => $statuses[array_rand($statuses)],
                'source' => $sources[array_rand($sources)]
            ]);
        }
    }
}