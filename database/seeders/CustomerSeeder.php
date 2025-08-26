<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'name' => 'Juan Pérez García',
                'email' => 'juan.perez@email.com',
                'phone' => '+51987654321',
                'document_type' => 'DNI',
                'document_number' => '12345678',
                'address' => 'Av. Javier Prado 1234, San Isidro',
                'city' => 'Lima',
                'state' => 'Lima',
                'zip_code' => '15073'
            ],
            [
                'name' => 'María González López',
                'email' => 'maria.gonzalez@email.com',
                'phone' => '+51912345678',
                'document_type' => 'DNI',
                'document_number' => '87654321',
                'address' => 'Jr. Lampa 567, Cercado de Lima',
                'city' => 'Lima',
                'state' => 'Lima',
                'zip_code' => '15001'
            ],
            [
                'name' => 'Carlos Mendoza Silva',
                'email' => 'carlos.mendoza@email.com',
                'phone' => '+51923456789',
                'document_type' => 'DNI',
                'document_number' => '11223344',
                'address' => 'Av. Brasil 890, Magdalena',
                'city' => 'Lima',
                'state' => 'Lima',
                'zip_code' => '15076'
            ],
            [
                'name' => 'Ana Torres Ruiz',
                'email' => 'ana.torres@email.com',
                'phone' => '+51934567890',
                'document_type' => 'DNI',
                'document_number' => '55667788',
                'address' => 'Calle Las Flores 234, Miraflores',
                'city' => 'Lima',
                'state' => 'Lima',
                'zip_code' => '15074'
            ],
            [
                'name' => 'Tech Solutions SAC',
                'email' => 'ventas@techsolutions.com',
                'phone' => '+51945678901',
                'document_type' => 'RUC',
                'document_number' => '20123456789',
                'address' => 'Av. El Sol 456, San Borja',
                'city' => 'Lima',
                'state' => 'Lima',
                'zip_code' => '15037'
            ]
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}