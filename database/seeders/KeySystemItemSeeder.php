<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KeySystemItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('key_system_items')->insert([
            [
                'key_activation' => 'ABC123-XYZ789',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_activation' => 'KEY-456-DEF-321',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_activation' => 'ACTIVATION-2025-TEST',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
