<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'published_year',
    ];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
        ];
    }
}
