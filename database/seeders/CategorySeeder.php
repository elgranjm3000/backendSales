<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Electrónicos',
                'description' => 'Dispositivos electrónicos y tecnología',
                'status' => 'active'
            ],
            [
                'name' => 'Ropa',
                'description' => 'Prendas de vestir y accesorios',
                'status' => 'active'
            ],
            [
                'name' => 'Hogar',
                'description' => 'Artículos para el hogar y decoración',
                'status' => 'active'
            ],
            [
                'name' => 'Deportes',
                'description' => 'Artículos deportivos y fitness',
                'status' => 'active'
            ],
            [
                'name' => 'Libros',
                'description' => 'Libros y material educativo',
                'status' => 'active'
            ],
            [
                'name' => 'Belleza',
                'description' => 'Productos de belleza y cuidado personal',
                'status' => 'active'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}