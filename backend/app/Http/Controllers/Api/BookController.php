<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Book::query()->latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'author' => ['required', 'string', 'max:255'],
                'published_year' => ['required', 'integer', 'min:1000', 'max:2100'],
            ]);
        } catch (ValidationException $exception) {
            Log::warning('Book validation failed', [
                'who' => 'authenticated_user',
                'action' => 'create_book',
                'reason' => 'required_book_fields_invalid',
                'fields' => array_keys($exception->errors()),
            ]);

            throw $exception;
        }

        try {
            $book = Book::create($validated);
        } catch (Throwable $exception) {
            Log::error('Book persistence failed', [
                'who' => 'authenticated_user',
                'action' => 'create_book',
                'reason' => 'database_write_failed',
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Buku gagal disimpan karena layanan basis data bermasalah.',
            ], 503);
        }

        Log::info('Book saved', [
            'who' => 'authenticated_user',
            'action' => 'create_book',
            'reason' => 'book_created_successfully',
        ]);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan.',
            'data' => $book,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        return response()->json(['data' => $book]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $book->update($request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'required', 'string', 'max:255'],
            'published_year' => ['sometimes', 'required', 'integer', 'min:1000', 'max:2100'],
        ]));

        return response()->json([
            'message' => 'Buku berhasil diubah.',
            'data' => $book->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus.',
        ]);
    }
}
