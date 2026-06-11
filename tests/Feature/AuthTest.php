<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it registers a user successfully', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('it fails registration if validation rules are violated', function () {
    // Missing fields
    $response = $this->postJson('/api/register', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    // Password confirmation mismatch & too short
    $response = $this->postJson('/api/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    // Duplicate email
    User::factory()->create(['email' => 'john@example.com']);
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('it logins a user successfully with correct credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
            ],
        ]);
});

test('it fails login with incorrect credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Credenciais inválidas.');
});

test('it returns authenticated user details', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('it blocks access to me endpoint if unauthenticated', function () {
    $response = $this->getJson('/api/me');

    $response->assertStatus(401);
});

test('it logs out a user successfully', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test_token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Sessão encerrada com sucesso.');

    expect($user->tokens()->count())->toBe(0);
});

test('it blocks logout endpoint if unauthenticated', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});
