<?php

use App\Models\Category;
use App\Models\Product;

test('it lists only active products', function () {
    Product::factory()->count(5)->create(['is_active' => true]);
    Product::factory()->count(3)->create(['is_active' => false]);

    $response = $this->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

test('it paginates active products with default limit', function () {
    Product::factory()->count(15)->create(['is_active' => true]);

    $response = $this->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.total', 15);
});

test('it accepts custom pagination limits', function () {
    Product::factory()->count(10)->create(['is_active' => true]);

    $response = $this->getJson('/api/products?per_page=5');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

test('it filters products by search term', function () {
    Product::factory()->create(['name' => 'Caneca Especial', 'description' => 'Um produto bacana', 'is_active' => true]);
    Product::factory()->create(['name' => 'Camisa Algodao', 'description' => 'Outra coisa legal', 'is_active' => true]);
    Product::factory()->create(['name' => 'Outro Item', 'description' => 'Descricao sem palavras chaves', 'is_active' => true]);

    // Busca por termo no nome
    $response = $this->getJson('/api/products?search=Caneca');
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Caneca Especial');

    // Busca por termo na descricao
    $response = $this->getJson('/api/products?search=coisa');
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Camisa Algodao');
});

test('it filters products by category slug', function () {
    $categoryA = Category::factory()->create(['slug' => 'calcados']);
    $categoryB = Category::factory()->create(['slug' => 'eletronicos']);

    Product::factory()->count(3)->create(['category_id' => $categoryA->id, 'is_active' => true]);
    Product::factory()->count(2)->create(['category_id' => $categoryB->id, 'is_active' => true]);

    $response = $this->getJson('/api/products?category=calcados');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('it sorts products by price ascending and descending', function () {
    Product::factory()->create(['price' => 10.00, 'is_active' => true]);
    Product::factory()->create(['price' => 50.00, 'is_active' => true]);
    Product::factory()->create(['price' => 25.00, 'is_active' => true]);

    // Ascending
    $response = $this->getJson('/api/products?sort_by=price&sort_order=asc');
    $response->assertStatus(200);
    $prices = collect($response->json('data'))->pluck('price')->toArray();
    expect($prices)->toEqual([10.00, 25.00, 50.00]);

    // Descending
    $response = $this->getJson('/api/products?sort_by=price&sort_order=desc');
    $response->assertStatus(200);
    $prices = collect($response->json('data'))->pluck('price')->toArray();
    expect($prices)->toEqual([50.00, 25.00, 10.00]);
});

test('it returns validation error for invalid parameters', function () {
    $response = $this->getJson('/api/products?per_page=-5');
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);

    $response = $this->getJson('/api/products?sort_by=invalid_field');
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sort_by']);

    $response = $this->getJson('/api/products?category=does-not-exist');
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category']);
});

test('it displays detail of a single active product', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $response = $this->getJson('/api/products/'.$product->slug);

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'price',
                'price_formatted',
                'stock',
                'image',
                'is_active',
                'category' => [
                    'id',
                    'name',
                    'slug',
                ],
            ],
        ]);
});

test('it returns 404 when displaying an inactive product', function () {
    $product = Product::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/products/'.$product->slug);

    $response->assertStatus(404);
});

test('it lists all categories ordered by name', function () {
    Category::factory()->create(['name' => 'Zapato']);
    Category::factory()->create(['name' => 'Armario']);
    Category::factory()->create(['name' => 'Computador']);

    $response = $this->getJson('/api/categories');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');

    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toBe(['Armario', 'Computador', 'Zapato']);
});
