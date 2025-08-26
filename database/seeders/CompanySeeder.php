<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios con rol company
        $companyUsers = User::where('role', User::ROLE_COMPANY)->get();

        if ($companyUsers->isEmpty()) {
            echo "❌ No hay usuarios con rol 'company'. Ejecutar UserSeeder primero.\n";
            return;
        }

        // Restaurant El Buen Sabor
        $user1 = $companyUsers->where('email', 'info@elbuensabor.com')->first();
        if ($user1) {
            Company::create([
                'user_id' => $user1->id,
                'name' => 'Restaurant El Buen Sabor',
                'description' => 'Restaurante familiar especializado en comida tradicional ecuatoriana con más de 15 años de experiencia.',
                'address' => 'Av. 10 de Agosto 1234, Quito, Ecuador',
                'phone' => '+593234567890',
                'email' => 'ventas@elbuensabor.com',
                'contact' => 'María Elena Vásquez',
                'serial_no' => 'RES001-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);

            // Segunda sucursal del mismo usuario
            Company::create([
                'user_id' => $user1->id,
                'name' => 'El Buen Sabor - Sucursal Norte',
                'description' => 'Sucursal norte del reconocido Restaurant El Buen Sabor, manteniendo la calidad y tradición.',
                'address' => 'Av. Amazonas 2567, Quito, Ecuador',
                'phone' => '+593234567891',
                'email' => 'norte@elbuensabor.com',
                'contact' => 'Carlos Mendoza',
                'serial_no' => 'RES001N-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        // Pizzería Italiana
        $user2 = $companyUsers->where('email', 'contacto@pizzeriaitaliana.com')->first();
        if ($user2) {
            Company::create([
                'user_id' => $user2->id,
                'name' => 'Pizzería Italiana Don Giovanni',
                'description' => 'Auténtica pizzería italiana con recetas tradicionales traídas directamente desde Nápoles.',
                'address' => 'Calle Italia 456, La Mariscal, Quito',
                'phone' => '+593345678901',
                'email' => 'pedidos@pizzeriaitaliana.com',
                'contact' => 'Giuseppe Rossi',
                'serial_no' => 'PIZ002-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        // Café Central
        $user3 = $companyUsers->where('email', 'gerencia@cafecentral.com')->first();
        if ($user3) {
            Company::create([
                'user_id' => $user3->id,
                'name' => 'Café Central',
                'description' => 'Cafetería boutique especializada en café de altura ecuatoriano y repostería artesanal.',
                'address' => 'Plaza San Francisco, Centro Histórico, Quito',
                'phone' => '+593456789012',
                'email' => 'info@cafecentral.com',
                'contact' => 'Andrea Morales',
                'serial_no' => 'CAF003-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);

            // Segunda sucursal de café
            Company::create([
                'user_id' => $user3->id,
                'name' => 'Café Central - Mall El Jardín',
                'description' => 'Sucursal moderna de Café Central ubicada en el centro comercial más visitado de la ciudad.',
                'address' => 'Mall El Jardín, Local 205, Quito',
                'phone' => '+593456789013',
                'email' => 'mall@cafecentral.com',
                'contact' => 'Roberto Silva',
                'serial_no' => 'CAF003M-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        // Marisquería Del Puerto
        $user4 = $companyUsers->where('email', 'admin@marisqueriadelpuerto.com')->first();
        if ($user4) {
            Company::create([
                'user_id' => $user4->id,
                'name' => 'Marisquería Del Puerto',
                'description' => 'Especialistas en mariscos frescos y ceviches preparados con pescado del día traído directamente del puerto.',
                'address' => 'Malecón Simón Bolívar 890, Guayaquil',
                'phone' => '+593567890123',
                'email' => 'reservas@marisqueriadelpuerto.com',
                'contact' => 'Captain Jorge Anchundia',
                'serial_no' => 'MAR004-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        // Crear algunas compañías adicionales para demostrar múltiples compañías por usuario
        if ($companyUsers->count() > 0) {
            // Compañía adicional para el primer usuario company
            $firstCompanyUser = $companyUsers->first();
            Company::create([
                'user_id' => $firstCompanyUser->id,
                'name' => 'Food Truck Delicias',
                'description' => 'Food truck móvil especializado en comida rápida gourmet para eventos y catering.',
                'address' => 'Ubicación variable - Quito y alrededores',
                'phone' => '+593234567892',
                'email' => 'foodtruck@elbuensabor.com',
                'contact' => 'Miguel Ángel Torres',
                'serial_no' => 'FTK001-2024',
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        // Crear una compañía inactiva para pruebas
        if ($companyUsers->count() > 1) {
            $secondCompanyUser = $companyUsers->skip(1)->first();
            Company::create([
                'user_id' => $secondCompanyUser->id,
                'name' => 'Pizzería Italiana - Sucursal Sur (Cerrada)',
                'description' => 'Sucursal temporalmente cerrada por remodelación.',
                'address' => 'Av. Quitumbe 123, Quito Sur',
                'phone' => '+593345678902',
                'email' => 'sur@pizzeriaitaliana.com',
                'contact' => 'Marco Benedetti',
                'serial_no' => 'PIZ002S-2024',
                'status' => Company::STATUS_INACTIVE,
            ]);
        }

        echo "✅ CompanySeeder completado: " . Company::count() . " compañías creadas\n";
        echo "📊 Distribución por usuario:\n";
        
        foreach ($companyUsers as $user) {
            $count = Company::where('user_id', $user->id)->count();
            echo "   - {$user->name}: {$count} compañía(s)\n";
        }
    }
}