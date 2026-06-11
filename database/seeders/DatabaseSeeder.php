<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create specific categories
        $categories = [
            'Tech' => Category::create(['name' => 'Tech', 'slug' => 'tech']),
            'Apparel' => Category::create(['name' => 'Apparel', 'slug' => 'apparel']),
            'Lifestyle' => Category::create(['name' => 'Lifestyle', 'slug' => 'lifestyle']),
        ];

        // 2. Create products with realistic details and Unsplash images
        $products = [
            [
                'category_id' => $categories['Tech']->id,
                'name' => 'AeroBuds Pro Max',
                'description' => 'Fones de ouvido com cancelamento de ruído ativo inteligente, som espacial imersivo de 360 graus e até 40 horas de autonomia. Acabamento em alumínio escovado e conchas ultra macias.',
                'price' => 1299.00,
                'stock' => 15,
                'image_path' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Tech']->id,
                'name' => 'Chronos Smartwatch S4',
                'description' => 'Monitoramento contínuo de saúde (ECG, oxigênio no sangue), tela AMOLED sempre ativa de alta definição, GPS de dupla frequência e resistência à água de até 50 metros.',
                'price' => 1899.00,
                'stock' => 8,
                'image_path' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Tech']->id,
                'name' => 'Teclado Mecânico Matrix 65',
                'description' => 'Teclado mecânico compacto layout 65%, switches lineares pré-lubrificados de fábrica, teclas PBT double-shot e iluminação RGB dinâmica totalmente customizável por software.',
                'price' => 849.00,
                'stock' => 5,
                'image_path' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Lifestyle']->id,
                'name' => 'Mochila Nomad Expandable',
                'description' => 'Mochila modular impermeável ideal para viagens e trabalho diário. Compartimento acolchoado para laptop de até 16", zíperes YKK ocultos e capacidade expansível de 20L para 30L.',
                'price' => 499.00,
                'stock' => 25,
                'image_path' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Lifestyle']->id,
                'name' => 'Garrafa Térmica HydroCore 950ml',
                'description' => 'Isolamento a vácuo de parede dupla que mantém bebidas geladas por até 24 horas e quentes por 12 horas. Construída em aço inoxidável 18/8 de grau alimentício com alça de transporte.',
                'price' => 189.00,
                'stock' => 50,
                'image_path' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Lifestyle']->id,
                'name' => 'Carteira de Couro Slim MagSafe',
                'description' => 'Carteira minimalista feita à mão em couro legítimo de grão integral. Possui blindagem RFID contra clonagem de cartões, capacidade para até 6 cartões e encaixe MagSafe magnético integrado.',
                'price' => 249.00,
                'stock' => 12,
                'image_path' => 'https://images.unsplash.com/photo-1627124765135-565b50355aa2?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Apparel']->id,
                'name' => 'Moletom Minimalist Oversized',
                'description' => 'Moletom confeccionado em algodão orgânico pesado de 400g/m² com toque macio peletizado. Modelagem oversized moderna, capuz estruturado de camada dupla e costuras reforçadas.',
                'price' => 359.00,
                'stock' => 30,
                'image_path' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ],
            [
                'category_id' => $categories['Apparel']->id,
                'name' => 'Boné Designer Corduroy',
                'description' => 'Boné de aba curva confeccionado em veludo cotelê de alta qualidade com fecho traseiro ajustável em fivela de latão. Design clássico desestruturado de 6 painéis.',
                'price' => 119.00,
                'stock' => 45,
                'image_path' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?q=80&w=600&auto=format&fit=crop',
                'is_active' => true,
            ]
        ];

        foreach ($products as $prod) {
            Product::create(array_merge($prod, [
                'slug' => Str::slug($prod['name']),
            ]));
        }

        // 3. Create default customer
        User::create([
            'name' => 'Marcondes Júnior',
            'email' => 'marcondes@emporia.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);

        // 4. Create default admin
        User::create([
            'name' => 'Admin Emporia',
            'email' => 'admin@emporia.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
    }
}
