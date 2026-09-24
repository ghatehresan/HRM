<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health', function () {
    $db = 'ok';

    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        report($e);
        $db = 'unreachable';
    }

    return response()->json([
        'app' => config('app.name'),
        'env' => app()->environment(),
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'db' => $db,
        'time_utc' => now('UTC')->toIso8601String(),
    ]);
})->name('health');
