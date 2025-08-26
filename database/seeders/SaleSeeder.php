<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;

class SaleSeeder extends Seeder
{
    public function run()
    {
        $customers = Customer::all();
        $sellers = User::where('role', 'seller')->get();
        $products = Product::all();

        // Crear ventas de los últimos 30 días
        for ($i = 30; $i >= 0; $i--) {
            $saleDate = Carbon::now()->subDays($i);
            
            // Crear 1-3 ventas por día
            $salesPerDay = rand(1, 3);
            
            for ($j = 0; $j < $salesPerDay; $j++) {
                $customer = $customers->random();
                $seller = $sellers->random();
                
                $sale = Sale::create([
                    'customer_id' => $customer->id,
                    'user_id' => $seller->id,
                    'subtotal' => 0, // Se calculará después
                    'tax' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'payment_method' => collect(['cash', 'card', 'transfer'])->random(),
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'sale_date' => $saleDate->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                    'notes' => rand(0, 1) ? 'Venta realizada con éxito' : null
                ]);

                // Agregar 1-4 productos a la venta
                $itemCount = rand(1, 4);
                $subtotal = 0;

                for ($k = 0; $k < $itemCount; $k++) {
                    $product = $products->random();
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $totalPrice = $quantity * $unitPrice;
                    
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'discount' => 0
                    ]);

                    $subtotal += $totalPrice;
                }

                // Actualizar totales de la venta
                $tax = $subtotal * 0.18;
                $total = $subtotal + $tax;

                $sale->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total
                ]);
            }
        }
    }
}