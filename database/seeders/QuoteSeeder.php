<?php
// database/seeders/QuoteSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;

class QuoteSeeder extends Seeder
{
    public function run()
    {
        $customers = Customer::all();
        $sellers = User::whereIn('role', ['seller', 'admin'])->get();
        $products = Product::all();

        // Crear presupuestos de los últimos 30 días
        for ($i = 30; $i >= 0; $i--) {
            $quoteDate = Carbon::now()->subDays($i);
            
            // Crear 1-4 presupuestos por día
            $quotesPerDay = rand(1, 4);
            
            for ($j = 0; $j < $quotesPerDay; $j++) {
                $customer = $customers->random();
                $seller = $sellers->random();
                
                // Determinar estado del presupuesto
                $statuses = ['draft', 'sent', 'approved', 'rejected'];
                $weights = [20, 40, 25, 15]; // Probabilidades
                $status = $this->weightedRandom($statuses, $weights);
                
                // Fecha de validez (15-45 días desde la fecha del presupuesto)
                $validUntil = $quoteDate->copy()->addDays(rand(15, 45));
                
                $quote = Quote::create([
                    'customer_id' => $customer->id,
                    'user_id' => $seller->id,
                    'subtotal' => 0, // Se calculará después
                    'tax' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'status' => $status,
                    'quote_date' => $quoteDate->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                    'valid_until' => $validUntil->toDateString(),
                    'terms_conditions' => $this->getRandomTermsConditions(),
                    'notes' => rand(0, 1) ? $this->getRandomNote() : null,
                    'sent_at' => in_array($status, ['sent', 'approved', 'rejected']) 
                        ? $quoteDate->copy()->addHours(rand(1, 24)) 
                        : null,
                    'approved_at' => $status === 'approved' 
                        ? $quoteDate->copy()->addDays(rand(1, 7)) 
                        : null
                ]);

                // Agregar 1-5 productos al presupuesto
                $itemCount = rand(1, 5);
                $subtotal = 0;

                for ($k = 0; $k < $itemCount; $k++) {
                    $product = $products->random();
                    $quantity = rand(1, 10);
                    $unitPrice = $product->price;
                    
                    // Aplicar descuento aleatorio en algunos casos
                    if (rand(1, 100) <= 20) { // 20% de probabilidad de descuento
                        $discountPercent = rand(5, 15);
                        $unitPrice = $unitPrice * (1 - $discountPercent / 100);
                    }
                    
                    $totalPrice = $quantity * $unitPrice;
                    
                    $quote->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'discount' => 0
                    ]);

                    $subtotal += $totalPrice;
                }

                // Aplicar descuento general en algunos presupuestos
                $generalDiscount = 0;
                if (rand(1, 100) <= 15) { // 15% de probabilidad
                    $generalDiscount = $subtotal * (rand(5, 20) / 100);
                }

                // Actualizar totales del presupuesto
                $tax = $subtotal * 0.18; // IGV 18%
                $total = $subtotal + $tax - $generalDiscount;

                $quote->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => $generalDiscount,
                    'total' => $total
                ]);
            }
        }

        // Crear algunos presupuestos que expiran pronto para testing
        $this->createExpiringSoonQuotes($customers, $sellers, $products);
    }

    private function weightedRandom($values, $weights)
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($values as $i => $value) {
            $currentWeight += $weights[$i];
            if ($random <= $currentWeight) {
                return $value;
            }
        }
        
        return $values[0];
    }

    private function getRandomTermsConditions()
    {
        $terms = [
            "Presupuesto válido por el tiempo indicado. Precios sujetos a cambios sin previo aviso.",
            "Los precios incluyen IGV. Tiempo de entrega: 5-10 días hábiles.",
            "Condiciones de pago: 50% al confirmar, 50% contra entrega. Garantía de 6 meses.",
            "Presupuesto válido hasta la fecha indicada. No incluye instalación ni transporte.",
            "Los precios son en soles peruanos. Sujeto a disponibilidad de stock."
        ];
        
        return $terms[array_rand($terms)];
    }

    private function getRandomNote()
    {
        $notes = [
            "Cliente interesado, seguimiento en 3 días",
            "Presupuesto solicitado por email",
            "Cliente corporativo - descuento aplicado",
            "Requiere facturación",
            "Entrega urgente solicitada",
            "Cliente frecuente - precio preferencial"
        ];
        
        return $notes[array_rand($notes)];
    }

    private function createExpiringSoonQuotes($customers, $sellers, $products)
    {
        // Crear 3 presupuestos que expiran en los próximos 7 días
        for ($i = 1; $i <= 7; $i += 2) {
            $customer = $customers->random();
            $seller = $sellers->random();
            
            $quote = Quote::create([
                'customer_id' => $customer->id,
                'user_id' => $seller->id,
                'subtotal' => 1000,
                'tax' => 180,
                'total' => 1180,
                'status' => 'sent',
                'quote_date' => now()->subDays(10),
                'valid_until' => now()->addDays($i)->toDateString(),
                'terms_conditions' => "Presupuesto con vencimiento próximo - Precio especial por tiempo limitado",
                'notes' => "⚠️ Expira pronto - Hacer seguimiento",
                'sent_at' => now()->subDays(9)
            ]);

            // Agregar un producto
            $product = $products->random();
            $quote->items()->create([
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 500,
                'total_price' => 1000,
                'discount' => 0
            ]);
        }
    }
}