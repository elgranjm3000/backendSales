<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            // Categorías para Restaurante El Buen Sabor
            [
                'company_id' => 1,
                'name' => 'Entradas',
                'description' => 'Aperitivos y entradas para abrir el apetito',
                'image' => 'entradas.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Platos Principales',
                'description' => 'Platos fuertes de la casa, especialidades criollas',
                'image' => 'principales.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Postres',
                'description' => 'Deliciosos postres caseros y tradicionales',
                'image' => 'postres.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Bebidas',
                'description' => 'Bebidas frías, calientes y jugos naturales',
                'image' => 'bebidas.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Categorías para Panadería San José
            [
                'company_id' => 2,
                'name' => 'Panes',
                'description' => 'Panes frescos horneados diariamente',
                'image' => 'panes.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 2,
                'name' => 'Pasteles',
                'description' => 'Pasteles y tortas para toda ocasión especial',
                'image' => 'pasteles.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 2,
                'name' => 'Galletas',
                'description' => 'Galletas artesanales de diversos sabores',
                'image' => 'galletas.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Categorías para Cafetería Central
            [
                'company_id' => 3,
                'name' => 'Cafés',
                'description' => 'Variedad de cafés especiales y bebidas calientes',
                'image' => 'cafes.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 3,
                'name' => 'Snacks',
                'description' => 'Snacks, bocadillos y comida rápida',
                'image' => 'snacks.jpg',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}