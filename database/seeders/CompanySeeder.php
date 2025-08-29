<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios con rol company
        DB::table('companies')->insert([
            [
                'user_id' => null,
                'name' => 'Restaurante El Buen Sabor',
                'rif' => 'J-30123456-7',
                'description' => 'Restaurante especializado en comida criolla venezolana con más de 15 años de experiencia',
                'address' => 'Av. Principal, Centro Comercial Plaza Venezuela, Local 15',
                'country' => 'Venezuela',
                'province' => 'Miranda',
                'city' => 'Caracas',
                'phone' => '+58 212-555-0101',
                'logo' => null,
                'logo_type' => null,
                'email' => 'info@elbuensabor.com',
                'contact' => 'María González',
                'key_system_items_id' => 1,
                'serial_no' => 'REST-001-2024',
                'restaurant_image' => null,
                'restaurant_image_type' => null,
                'main_image' => null,
                'main_image_type' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'name' => 'Panadería San José',
                'rif' => 'J-31234567-8',
                'description' => 'Panadería artesanal familiar con más de 20 años elaborando productos frescos diariamente',
                'address' => 'Calle 5ta con Av. Libertador, Sector La Candelaria',
                'country' => 'Venezuela',
                'province' => 'Distrito Capital',
                'city' => 'Caracas',
                'phone' => '+58 212-555-0102',
                'logo' => null,
                'logo_type' => null,
                'email' => 'ventas@panaderiasanjose.com',
                'contact' => 'José Rodríguez',
                'key_system_items_id' => 2,
                'serial_no' => 'PAN-002-2024',
                'restaurant_image' => null,
                'restaurant_image_type' => null,
                'main_image' => null,
                'main_image_type' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'name' => 'Cafetería Central',
                'rif' => 'J-32345678-9',
                'description' => 'Cafetería moderna con ambiente acogedor, especializada en cafés especiales y comida rápida',
                'address' => 'Plaza Bolívar, Edificio Centro, Local 15-16',
                'country' => 'Venezuela',
                'province' => 'Carabobo',
                'city' => 'Valencia',
                'phone' => '+58 241-555-0103',
                'logo' => null,
                'logo_type' => null,
                'email' => 'contacto@cafeteriacentral.com',
                'contact' => 'Ana Martínez',
                'key_system_items_id' => 3,
                'serial_no' => 'CAF-003-2024',
                'restaurant_image' => null,
                'restaurant_image_type' => null,
                'main_image' => null,
                'main_image_type' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}