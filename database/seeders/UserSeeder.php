<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Admin
        User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@sistema.com',
            'phone' => '+593987654321',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Usuarios Manager
        User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'carlos.mendoza@sistema.com',
            'phone' => '+593123456789',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Ana Rodriguez',
            'email' => 'ana.rodriguez@sistema.com',
            'phone' => '+593987123456',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Usuarios Company
        User::create([
            'name' => 'Restaurant El Buen Sabor',
            'email' => 'info@elbuensabor.com',
            'phone' => '+593234567890',
            'role' => User::ROLE_COMPANY,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Pizzería Italiana',
            'email' => 'contacto@pizzeriaitaliana.com',
            'phone' => '+593345678901',
            'role' => User::ROLE_COMPANY,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Pizzería Italiana',
            'email' => 'elgranjm3000@gmail.com',
            'phone' => '+593345678901',
            'role' => User::ROLE_COMPANY,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Café Central',
            'email' => 'gerencia@cafecentral.com',
            'phone' => '+593456789012',
            'role' => User::ROLE_COMPANY,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Marisquería Del Puerto',
            'email' => 'admin@marisqueriadelpuerto.com',
            'phone' => '+593567890123',
            'role' => User::ROLE_COMPANY,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Usuarios Seller
        User::create([
            'name' => 'María González',
            'email' => 'maria.gonzalez@email.com',
            'phone' => '+593111222333',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@email.com',
            'phone' => '+593222333444',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Luis Torres',
            'email' => 'luis.torres@email.com',
            'phone' => '+593333444555',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Carmen Silva',
            'email' => 'carmen.silva@email.com',
            'phone' => '+593444555666',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Roberto Morales',
            'email' => 'roberto.morales@email.com',
            'phone' => '+593555666777',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Patricia Vega',
            'email' => 'patricia.vega@email.com',
            'phone' => '+593666777888',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Fernando Castro',
            'email' => 'fernando.castro@email.com',
            'phone' => '+593777888999',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Isabel Herrera',
            'email' => 'isabel.herrera@email.com',
            'phone' => '+593888999000',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Diego Ramírez',
            'email' => 'diego.ramirez@email.com',
            'phone' => '+593999000111',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Sofía Flores',
            'email' => 'sofia.flores@email.com',
            'phone' => '+593000111222',
            'role' => User::ROLE_SELLER,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        echo "✅ UserSeeder completado: " . User::count() . " usuarios creados\n";
    }
}