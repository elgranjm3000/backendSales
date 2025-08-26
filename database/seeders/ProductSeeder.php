<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $electronics = Category::where('name', 'Electrónicos')->first();
        $clothing = Category::where('name', 'Ropa')->first();
        $home = Category::where('name', 'Hogar')->first();
        $sports = Category::where('name', 'Deportes')->first();

        $products = [
            // Electrónicos
            [
                'name' => 'iPhone 15 Pro',
                'code' => 'IP15PRO001',
                'description' => 'Smartphone Apple iPhone 15 Pro 128GB',
                'price' => 3599.00,
                'cost' => 2800.00,
                'stock' => 15,
                'min_stock' => 5,
                'category_id' => $electronics->id,
                'barcode' => '1234567890123',
                'weight' => 0.187
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'code' => 'SGS24001',
                'description' => 'Smartphone Samsung Galaxy S24 256GB',
                'price' => 2899.00,
                'cost' => 2200.00,
                'stock' => 20,
                'min_stock' => 5,
                'category_id' => $electronics->id,
                'barcode' => '2345678901234'
            ],
            [
                'name' => 'MacBook Air M3',
                'code' => 'MBA001',
                'description' => 'Laptop Apple MacBook Air 13" M3 8GB 256GB',
                'price' => 4999.00,
                'cost' => 4200.00,
                'stock' => 8,
                'min_stock' => 3,
                'category_id' => $electronics->id,
                'weight' => 1.24
            ],
            [
                'name' => 'AirPods Pro 2',
                'code' => 'APP2001',
                'description' => 'Audífonos inalámbricos Apple AirPods Pro 2da Gen',
                'price' => 899.00,
                'cost' => 650.00,
                'stock' => 25,
                'min_stock' => 10,
                'category_id' => $electronics->id,
                'weight' => 0.061
            ],

            // Ropa
            [
                'name' => 'Polo Nike Dri-FIT',
                'code' => 'POLO001',
                'description' => 'Polo deportivo Nike Dri-FIT para hombre',
                'price' => 89.90,
                'cost' => 55.00,
                'stock' => 50,
                'min_stock' => 15,
                'category_id' => $clothing->id,
                'attributes' => ['tallas' => ['S', 'M', 'L', 'XL'], 'colores' => ['Negro', 'Blanco', 'Azul']]
            ],
            [
                'name' => 'Jeans Levis 501',
                'code' => 'JEAN001',
                'description' => 'Jeans clásicos Levis 501 Original',
                'price' => 299.00,
                'cost' => 180.00,
                'stock' => 30,
                'min_stock' => 10,
                'category_id' => $clothing->id,
                'attributes' => ['tallas' => ['28', '30', '32', '34', '36']]
            ],

            // Hogar
            [
                'name' => 'Cafetera Nespresso',
                'code' => 'CAF001',
                'description' => 'Cafetera Nespresso Vertuo Next',
                'price' => 459.00,
                'cost' => 320.00,
                'stock' => 12,
                'min_stock' => 5,
                'category_id' => $home->id,
                'weight' => 4.0
            ],
            [
                'name' => 'Aspiradora Dyson V15',
                'code' => 'ASP001',
                'description' => 'Aspiradora inalámbrica Dyson V15 Detect',
                'price' => 2199.00,
                'cost' => 1650.00,
                'stock' => 6,
                'min_stock' => 2,
                'category_id' => $home->id,
                'weight' => 3.1
            ],

            // Deportes
            [
                'name' => 'Zapatillas Nike Air Max',
                'code' => 'ZAP001',
                'description' => 'Zapatillas Nike Air Max 270 para running',
                'price' => 399.00,
                'cost' => 250.00,
                'stock' => 40,
                'min_stock' => 15,
                'category_id' => $sports->id,
                'attributes' => ['tallas' => ['39', '40', '41', '42', '43', '44']]
            ],
            [
                'name' => 'Pelota de Fútbol Adidas',
                'code' => 'PEL001',
                'description' => 'Pelota de fútbol Adidas FIFA Quality Pro',
                'price' => 89.00,
                'cost' => 55.00,
                'stock' => 25,
                'min_stock' => 8,
                'category_id' => $sports->id,
                'weight' => 0.41
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}