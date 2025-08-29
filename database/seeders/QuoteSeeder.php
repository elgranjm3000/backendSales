<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quotes')->insert([
            [
                'quote_number' => 'COT-2024-001',
                'customer_id' => 1,
                'company_id' => 1,
                'subtotal' => 68.00,
                'tax' => 10.88,
                'discount' => 3.40,
                'total' => 75.48,
                'status' => 'sent',
                'notes' => 'Cotización para evento familiar de cumpleaños. Cliente frecuente con descuento especial.',
                'terms_conditions' => 'Válida por 15 días. Precios sujetos a cambios sin previo aviso. Pago al momento de la entrega.',
                'quote_date' => now(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'sent_at' => now(),
                'approved_at' => null,
                'metadata' => json_encode([
                    'evento' => 'cumpleaños familiar',
                    'personas_estimadas' => 10,
                    'vendedor' => 'María González'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quote_number' => 'COT-2024-002',
                'customer_id' => 2,
                'company_id' => 1,
                'subtotal' => 350.00,
                'tax' => 56.00,
                'discount' => 17.50,
                'total' => 388.50,
                'status' => 'approved',
                'notes' => 'Cotización para catering empresarial - reunión mensual de directorio. Incluye servicio completo.',
                'terms_conditions' => 'Pago 50% anticipo, 50% contra entrega. Válida por 30 días. Servicio incluye montaje y desmontaje.',
                'quote_date' => now()->subDays(5),
                'valid_until' => now()->addDays(25)->toDateString(),
                'sent_at' => now()->subDays(5),
                'approved_at' => now()->subDays(2),
                'metadata' => json_encode([
                    'tipo' => 'catering empresarial',
                    'personas' => 50,
                    'servicio_completo' => true,
                    'ubicacion' => 'Torre Empresarial Cavendes'
                ]),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(2),
            ],
            [
                'quote_number' => 'COT-2024-003',
                'customer_id' => 3,
                'company_id' => 2,
                'subtotal' => 120.00,
                'tax' => 19.20,
                'discount' => 6.00,
                'total' => 133.20,
                'status' => 'draft',
                'notes' => 'Cotización para pedido semanal de panadería. Cliente solicita productos integrales.',
                'terms_conditions' => 'Válida por 7 días. Entrega semanal los lunes. Descuento por volumen aplicado.',
                'quote_date' => now()->subDays(1),
                'valid_until' => now()->addDays(6)->toDateString(),
                'sent_at' => null,
                'approved_at' => null,
                'metadata' => json_encode([
                    'tipo' => 'pedido semanal',
                    'dia_entrega' => 'lunes',
                    'productos_preferidos' => 'integrales'
                ]),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'quote_number' => 'COT-2024-004',
                'customer_id' => 4,
                'company_id' => 3,
                'subtotal' => 85.00,
                'tax' => 13.60,
                'discount' => 0.00,
                'total' => 98.60,
                'status' => 'sent',
                'notes' => 'Cotización para desayunos corporativos durante la semana. Cliente regular de la cafetería.',
                'terms_conditions' => 'Válida por 10 días. Pago semanal. Horario de entrega: 7:00 AM - 9:00 AM.',
                'quote_date' => now()->subDays(3),
                'valid_until' => now()->addDays(7)->toDateString(),
                'sent_at' => now()->subDays(3),
                'approved_at' => null,
                'metadata' => json_encode([
                    'tipo' => 'desayunos corporativos',
                    'horario_entrega' => '7:00 AM - 9:00 AM',
                    'frecuencia' => 'semanal'
                ]),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'quote_number' => 'COT-2024-005',
                'customer_id' => 5,
                'company_id' => 1,
                'subtotal' => 42.00,
                'tax' => 6.72,
                'discount' => 2.10,
                'total' => 46.62,
                'status' => 'rejected',
                'notes' => 'Cotización inicial para cliente nuevo. Cliente decidió evaluar otras opciones.',
                'terms_conditions' => 'Válida por 15 días. Descuento por cliente nuevo aplicado. Precios sujetos a cambios.',
                'quote_date' => now()->subDays(10),
                'valid_until' => now()->addDays(5)->toDateString(),
                'sent_at' => now()->subDays(10),
                'approved_at' => null,
                'metadata' => json_encode([
                    'cliente' => 'nuevo',
                    'motivo_rechazo' => 'evaluando opciones',
                    'seguimiento' => 'pendiente'
                ]),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(7),
            ],
        ]);
    }
}