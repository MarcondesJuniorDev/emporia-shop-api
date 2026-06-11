<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('it blocks anonymous users from accessing admin routes', function () {
    $this->postJson('/api/admin/categories', ['name' => 'Teste'])->assertStatus(401);
    $this->postJson('/api/admin/products', [])->assertStatus(401);
});

test('it blocks non-admin users from accessing admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/admin/categories', ['name' => 'Teste'])
        ->assertStatus(403);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/admin/products', [])
        ->assertStatus(403);
});

test('it allows admin to create, update and delete a category', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    // Create Category
    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/categories', [
        'name' => 'Acessorios de Esporte',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Acessorios de Esporte')
        ->assertJsonPath('data.slug', 'acessorios-de-esporte');

    $this->assertDatabaseHas('categories', ['name' => 'Acessorios de Esporte']);
    $categoryId = $response->json('data.id');

    // Update Category
    $response = $this->actingAs($admin, 'sanctum')->putJson('/api/admin/categories/'.$categoryId, [
        'name' => 'Acessorios Esportivos',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Acessorios Esportivos')
        ->assertJsonPath('data.slug', 'acessorios-esportivos');

    $this->assertDatabaseHas('categories', ['name' => 'Acessorios Esportivos']);

    // Delete Category
    $this->actingAs($admin, 'sanctum')->deleteJson('/api/admin/categories/'.$categoryId)
        ->assertStatus(200)
        ->assertJsonPath('message', 'Categoria excluída com sucesso.');

    $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
});

test('it allows admin to create a product with image upload', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $imageFile = UploadedFile::fake()->create('tenis.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/products', [
        'category_id' => $category->id,
        'name' => 'Tenis de Corrida Top',
        'description' => 'Super confortavel',
        'price' => 299.90,
        'stock' => 50,
        'is_active' => true,
        'image' => $imageFile,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Tenis de Corrida Top')
        ->assertJsonPath('data.slug', 'tenis-de-corrida-top')
        ->assertJsonPath('data.price', 299.9)
        ->assertJsonPath('data.stock', 50);

    $productId = $response->json('data.id');
    $product = Product::find($productId);

    // Verify image was stored on disk
    expect($product->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image_path);
});

test('it allows admin to update a product and replace image', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();

    // Create product with initial image
    $initialImage = UploadedFile::fake()->create('tenis-old.jpg', 100, 'image/jpeg');
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Tenis Antigo',
        'image_path' => $initialImage->store('products', 'public'),
    ]);

    Storage::disk('public')->assertExists($product->image_path);
    $oldImagePath = $product->image_path;

    // Update product with new image
    $newImage = UploadedFile::fake()->create('tenis-new.jpg', 100, 'image/jpeg');
    $response = $this->actingAs($admin, 'sanctum')->putJson('/api/admin/products/'.$product->id, [
        'category_id' => $category->id,
        'name' => 'Tenis Modernizado',
        'price' => 350.00,
        'stock' => 10,
        'image' => $newImage,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Tenis Modernizado');

    // Old image should be deleted, new image should exist
    Storage::disk('public')->assertMissing($oldImagePath);
    Storage::disk('public')->assertExists($product->fresh()->image_path);
});

test('it allows admin to delete a product and delete image', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $image = UploadedFile::fake()->create('tenis.jpg', 100, 'image/jpeg');
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'image_path' => $image->store('products', 'public'),
    ]);

    Storage::disk('public')->assertExists($product->image_path);
    $imagePath = $product->image_path;

    $this->actingAs($admin, 'sanctum')->deleteJson('/api/admin/products/'.$product->id)
        ->assertStatus(200);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
    Storage::disk('public')->assertMissing($imagePath);
});
