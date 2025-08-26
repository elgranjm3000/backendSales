<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Seller;
use App\Models\User;
use App\Models\Company;

class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios con rol seller y compañías
        $sellerUsers = User::where('role', User::ROLE_SELLER)->get();
        $companies = Company::where('status', Company::STATUS_ACTIVE)->get();

        if ($sellerUsers->isEmpty()) {
            echo "❌ No hay usuarios con rol 'seller'. Ejecutar UserSeeder primero.\n";
            return;
        }

        if ($companies->isEmpty()) {
            echo "❌ No hay compañías activas. Ejecutar CompanySeeder primero.\n";
            return;
        }

        $sellerCode = 1000; // Código inicial para vendedores

        // Definir datos específicos para cada vendedor
        $sellersData = [
            [
                'user' => 'maria.gonzalez@email.com',
                'companies' => ['Restaurant El Buen Sabor'],
                'data' => [
                    'description' => 'Vendedora senior especializada en atención al cliente y ventas de platos principales',
                    'status' => 'Activa - Tiempo Completo',
                    'percent_sales' => 4.5,
                    'percent_receivable' => 2.0,
                    'inkeeper' => true,
                    'percent_gerencial_debit_note' => 1.0,
                    'percent_gerencial_credit_note' => 1.5,
                    'percent_returned_check' => 0.5,
                ]
            ],
            [
                'user' => 'juan.perez@email.com', 
                'companies' => ['Restaurant El Buen Sabor', 'El Buen Sabor - Sucursal Norte'],
                'data' => [
                    'description' => 'Vendedor con experiencia en eventos y catering corporativo',
                    'status' => 'Activo - Medio Tiempo',
                    'percent_sales' => 3.8,
                    'percent_receivable' => 1.5,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 0.8,
                    'percent_gerencial_credit_note' => 1.2,
                    'percent_returned_check' => 0.3,
                ]
            ],
            [
                'user' => 'luis.torres@email.com',
                'companies' => ['Pizzería Italiana Don Giovanni'],
                'data' => [
                    'description' => 'Especialista en pizzas artesanales y atención de mesas VIP',
                    'status' => 'Activo - Tiempo Completo',
                    'percent_sales' => 5.0,
                    'percent_receivable' => 2.5,
                    'inkeeper' => true,
                    'percent_gerencial_debit_note' => 1.2,
                    'percent_gerencial_credit_note' => 1.8,
                    'percent_returned_check' => 0.6,
                ]
            ],
            [
                'user' => 'carmen.silva@email.com',
                'companies' => ['Café Central'],
                'data' => [
                    'description' => 'Barista certificada y vendedora de productos de repostería',
                    'status' => 'Activa - Tiempo Completo',
                    'percent_sales' => 4.2,
                    'percent_receivable' => 1.8,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 0.9,
                    'percent_gerencial_credit_note' => 1.3,
                    'percent_returned_check' => 0.4,
                ]
            ],
            [
                'user' => 'roberto.morales@email.com',
                'companies' => ['Café Central - Mall El Jardín'],
                'data' => [
                    'description' => 'Vendedor especializado en bebidas premium y productos para llevar',
                    'status' => 'Activo - Tiempo Completo',
                    'percent_sales' => 3.5,
                    'percent_receivable' => 1.2,
                    'inkeeper' => true,
                    'percent_gerencial_debit_note' => 0.7,
                    'percent_gerencial_credit_note' => 1.0,
                    'percent_returned_check' => 0.2,
                ]
            ],
            [
                'user' => 'patricia.vega@email.com',
                'companies' => ['Marisquería Del Puerto'],
                'data' => [
                    'description' => 'Experta en mariscos y ceviches, conocedora de productos del mar',
                    'status' => 'Activa - Tiempo Completo',
                    'percent_sales' => 5.5,
                    'percent_receivable' => 3.0,
                    'inkeeper' => true,
                    'percent_gerencial_debit_note' => 1.5,
                    'percent_gerencial_credit_note' => 2.0,
                    'percent_returned_check' => 0.8,
                ]
            ],
            [
                'user' => 'fernando.castro@email.com',
                'companies' => ['Food Truck Delicias'],
                'data' => [
                    'description' => 'Vendedor móvil especializado en eventos y catering express',
                    'status' => 'Activo - Por Eventos',
                    'percent_sales' => 6.0,
                    'percent_receivable' => 2.8,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 1.8,
                    'percent_gerencial_credit_note' => 2.2,
                    'percent_returned_check' => 1.0,
                ]
            ],
            [
                'user' => 'isabel.herrera@email.com',
                'companies' => ['Restaurant El Buen Sabor', 'Pizzería Italiana Don Giovanni'],
                'data' => [
                    'description' => 'Vendedora con experiencia multicultural, especialista en atención turística',
                    'status' => 'Activa - Tiempo Completo',
                    'percent_sales' => 4.8,
                    'percent_receivable' => 2.2,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 1.1,
                    'percent_gerencial_credit_note' => 1.6,
                    'percent_returned_check' => 0.5,
                ]
            ],
            [
                'user' => 'diego.ramirez@email.com',
                'companies' => ['Café Central'],
                'data' => [
                    'description' => 'Vendedor nocturno especializado en eventos privados y cenas románticas',
                    'status' => 'Activo - Turno Noche',
                    'percent_sales' => 4.0,
                    'percent_receivable' => 1.6,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 0.8,
                    'percent_gerencial_credit_note' => 1.1,
                    'percent_returned_check' => 0.3,
                ]
            ],
            [
                'user' => 'sofia.flores@email.com',
                'companies' => ['Marisquería Del Puerto'],
                'data' => [
                    'description' => 'Vendedora junior en entrenamiento, especializada en platos del día',
                    'status' => 'En Entrenamiento',
                    'percent_sales' => 2.5,
                    'percent_receivable' => 1.0,
                    'inkeeper' => false,
                    'percent_gerencial_debit_note' => 0.5,
                    'percent_gerencial_credit_note' => 0.7,
                    'percent_returned_check' => 0.2,
                ]
            ],
        ];

        foreach ($sellersData as $sellerData) {
            $user = $sellerUsers->where('email', $sellerData['user'])->first();
            
            if (!$user) {
                echo "⚠️  Usuario {$sellerData['user']} no encontrado\n";
                continue;
            }

            foreach ($sellerData['companies'] as $companyName) {
                $company = $companies->where('name', $companyName)->first();
                
                if (!$company) {
                    echo "⚠️  Compañía '{$companyName}' no encontrada\n";
                    continue;
                }

                // Verificar si ya existe el vendedor en esta compañía
                $existingSeller = Seller::where('user_id', $user->id)
                                        ->where('company_id', $company->id)
                                        ->first();

                if ($existingSeller) {
                    echo "⚠️  {$user->name} ya es vendedor en {$company->name}\n";
                    continue;
                }

                $sellerCode++;
                $userCode = 'USR' . str_pad($user->id, 3, '0', STR_PAD_LEFT);

                Seller::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'code' => 'VEND' . $sellerCode,
                    'description' => $sellerData['data']['description'],
                    'status' => $sellerData['data']['status'],
                    'percent_sales' => $sellerData['data']['percent_sales'],
                    'percent_receivable' => $sellerData['data']['percent_receivable'],
                    'inkeeper' => $sellerData['data']['inkeeper'],
                    'user_code' => $userCode,
                    'percent_gerencial_debit_note' => $sellerData['data']['percent_gerencial_debit_note'],
                    'percent_gerencial_credit_note' => $sellerData['data']['percent_gerencial_credit_note'],
                    'percent_returned_check' => $sellerData['data']['percent_returned_check'],
                    'seller_status' => Seller::STATUS_ACTIVE,
                ]);

                echo "✅ Vendedor creado: {$user->name} en {$company->name}\n";
            }
        }

        // Crear algunos vendedores con estado inactivo para pruebas
        $inactiveUsers = $sellerUsers->slice(-2); // Últimos 2 usuarios
        foreach ($inactiveUsers as $user) {
            $company = $companies->first(); // Primera compañía disponible
            
            // Verificar que no exista ya
            $existing = Seller::where('user_id', $user->id)
                             ->where('company_id', $company->id)
                             ->first();
            
            if (!$existing) {
                $sellerCode++;
                Seller::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'code' => 'VEND' . $sellerCode,
                    'description' => 'Vendedor temporalmente inactivo',
                    'status' => 'Suspendido',
                    'percent_sales' => 0,
                    'percent_receivable' => 0,
                    'inkeeper' => false,
                    'user_code' => 'USR' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'percent_gerencial_debit_note' => 0,
                    'percent_gerencial_credit_note' => 0,
                    'percent_returned_check' => 0,
                    'seller_status' => Seller::STATUS_INACTIVE,
                ]);
                echo "✅ Vendedor inactivo creado: {$user->name}\n";
            }
        }

        echo "\n✅ SellerSeeder completado: " . Seller::count() . " vendedores creados\n";
        echo "📊 Estadísticas:\n";
        echo "   - Vendedores activos: " . Seller::where('seller_status', 'active')->count() . "\n";
        echo "   - Vendedores inactivos: " . Seller::where('seller_status', 'inactive')->count() . "\n";
        echo "   - Posaderos/Encargados: " . Seller::where('inkeeper', true)->count() . "\n";
        
        // Mostrar distribución por compañía
        echo "📈 Vendedores por compañía:\n";
        foreach ($companies as $company) {
            $count = Seller::where('company_id', $company->id)->count();
            echo "   - {$company->name}: {$count} vendedor(es)\n";
        }
    }
}