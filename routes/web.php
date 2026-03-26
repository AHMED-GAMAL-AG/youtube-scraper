<?php

use App\Http\Controllers\FetchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlaylistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/fetch/start', [FetchController::class, 'start'])->name('fetch.start');
Route::get('/fetch/{fetchJob}/status', [FetchController::class, 'status'])->name('fetch.status');
Route::post('/fetch/{fetchJob}/stop', [FetchController::class, 'stop'])->name('fetch.stop');

Route::get('/api/playlists', [PlaylistController::class, 'index'])->name('playlists.index');
