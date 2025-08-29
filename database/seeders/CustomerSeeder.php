<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        DB::table('customers')->insert([
            [
                'company_id' => 1,
                'name' => 'Carlos Pérez',
                'email' => 'carlos.perez@gmail.com',
                'phone' => '+58 414-123-4567',
                'document_type' => 'CI',
                'document_number' => '12345678',
                'address' => 'Urb. Los Rosales, Casa 123, Calle Principal',
                'city' => 'Caracas',
                'state' => 'Miranda',
                'zip_code' => '1010',
                'latitude' => 10.4806,
                'longitude' => -66.9036,
                'status' => 'active',
                'additional_info' => json_encode([
                    'tipo_cliente' => 'frecuente',
                    'descuento_preferencial' => '5%',
                    'preferencias' => 'sin picante'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Empresa ABC C.A.',
                'email' => 'compras@empresaabc.com',
                'phone' => '+58 212-987-6543',
                'document_type' => 'RIF',
                'document_number' => 'J-40123456-7',
                'address' => 'Torre Empresarial Cavendes, Piso 15, Oficina 1501',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'zip_code' => '1050',
                'latitude' => 10.5000,
                'longitude' => -66.9167,
                'status' => 'active',
                'additional_info' => json_encode([
                    'tipo_cliente' => 'corporativo',
                    'credito_dias' => 30,
                    'contacto_compras' => 'Lcdo. Roberto Silva'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 2,
                'name' => 'María López',
                'email' => 'maria.lopez@hotmail.com',
                'phone' => '+58 426-555-1234',
                'document_type' => 'CI',
                'document_number' => '87654321',
                'address' => 'Av. Libertador, Res. El Parque, Apto 5-B',
                'city' => 'Caracas',
                'state' => 'Miranda',
                'zip_code' => '1020',
                'latitude' => 10.4900,
                'longitude' => -66.8800,
                'status' => 'active',
                'additional_info' => json_encode([
                    'preferencias' => ['pan integral', 'productos sin azúcar'],
                    'alergias' => 'ninguna'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 3,
                'name' => 'Roberto Silva',
                'email' => 'roberto.silva@gmail.com',
                'phone' => '+58 414-777-8888',
                'document_type' => 'CI',
                'document_number' => '11223344',
                'address' => 'Centro de Valencia, Calle 102 entre Av. Bolívar y Av. Soublette',
                'city' => 'Valencia',
                'state' => 'Carabobo',
                'zip_code' => '2001',
                'latitude' => 10.1617,
                'longitude' => -67.9911,
                'status' => 'active',
                'additional_info' => json_encode([
                    'bebida_favorita' => 'cappuccino',
                    'horario_preferido' => 'mañana'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Elena Ramírez',
                'email' => 'elena.ramirez@yahoo.com',
                'phone' => '+58 424-999-5555',
                'document_type' => 'CI',
                'document_number' => '55667788',
                'address' => 'Las Mercedes, Calle París, Res. Eurobuilding',
                'city' => 'Caracas',
                'state' => 'Miranda',
                'zip_code' => '1060',
                'latitude' => 10.4950,
                'longitude' => -66.8600,
                'status' => 'active',
                'additional_info' => json_encode([
                    'tipo_cliente' => 'nuevo',
                    'referido_por' => 'Carlos Pérez'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}