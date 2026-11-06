<?php

use App\Http\Controllers\HealthController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->withoutMiddleware(StartSession::class);
Route::get('/', function () {
    return view('welcome');
});
