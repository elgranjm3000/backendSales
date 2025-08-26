<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,      // Primero usuarios (sin dependencias)
            KeySystemItemSeeder::class, // Claves de activación
            //CompanySeeder::class,   // Segundo compañías (dependen de usuarios)
            SellerSeeder::class,
            CategorySeeder::class,  // Categorías
            ProductSeeder::class,   // Productos (dependen de categorías)
            
        ]);
    }
}
