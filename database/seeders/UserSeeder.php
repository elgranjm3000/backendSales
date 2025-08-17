<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@ventas.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+51987654321',
            'status' => 'active'
        ]);

        User::create([
            'name' => 'Vendedor 1',
            'email' => 'vendedor1@ventas.com',
            'password' => Hash::make('password'),
            'role' => 'seller',
            'phone' => '+51912345678',
            'status' => 'active'
        ]);

        User::create([
            'name' => 'Vendedor 2',
            'email' => 'vendedor2@ventas.com',
            'password' => Hash::make('password'),
            'role' => 'seller',
            'phone' => '+51923456789',
            'status' => 'active'
        ]);

        User::create([
            'name' => 'Gerente',
            'email' => 'gerente@ventas.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'phone' => '+51934567890',
            'status' => 'active'
        ]);
    }
}