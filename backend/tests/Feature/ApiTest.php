<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@buku.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_books_are_protected_without_a_token(): void
    {
        $response = $this->getJson('/api/books');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_a_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/books', [
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'published_year' => 2005,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Laskar Pelangi');
        $this->assertDatabaseHas('books', ['title' => 'Laskar Pelangi']);
    }

    public function test_book_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'author', 'published_year']);
    }
}
