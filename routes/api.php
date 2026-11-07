<?php

use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StatsController::class, 'dashboard']);
Route::get('/index.php', [StatsController::class, 'dashboard']);
Route::get('/charts.json', [StatsController::class, 'charts'])->name('charts');
Route::match(['get', 'post'], '/data.php', [StatsController::class, 'chart']);
Route::match(['get', 'post'], '/log.php', [StatsController::class, 'store']);
Route::match(['get', 'post'], '/clear.php', [StatsController::class, 'clear']);
