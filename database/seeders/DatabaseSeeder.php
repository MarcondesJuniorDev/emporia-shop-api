<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = Category::factory()->count(5)->create();

        foreach ($categories as $category) {
            Product::factory()
                ->count(rand(8, 12))
                ->create([
                    'category_id' => $category->id,
                ]);
        }
    }
}
