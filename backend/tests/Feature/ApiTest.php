<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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

        Log::spy();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'author', 'published_year']);

        Log::shouldHaveReceived('warning')
            ->with('Book validation failed', \Mockery::on(fn (array $context): bool => $context['who'] === 'authenticated_user'
                && $context['action'] === 'create_book'
                && $context['reason'] === 'required_book_fields_invalid'));
    }

    public function test_health_endpoint_checks_database_and_cache(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
            ]);
    }
}
