<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipantPortalController;
use App\Http\Controllers\RaceRouteController;
use App\Http\Controllers\RfidOperatorController;
use Illuminate\Support\Facades\Route;

// Main Operator Scanning Screen (Default Entry)
Route::get('/', [RfidOperatorController::class, 'index'])->name('home');
Route::get('/scan', [RfidOperatorController::class, 'index'])->name('rfid.scan');

// Dashboard peserta via link/QR unik (tanpa login). Dibatasi rate limit untuk menahan tebak-tebakan token.
Route::prefix('p/{token}')->name('participant.')->middleware('throttle:120,1')->group(function () {
    Route::get('/', [ParticipantPortalController::class, 'show'])->name('dashboard');
    Route::post('/runs', [ParticipantPortalController::class, 'storeRun'])->middleware('throttle:10,1')->name('runs.store');
    Route::delete('/runs/{run}', [ParticipantPortalController::class, 'destroyRun'])->name('runs.destroy');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Admin Area
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/scan-logs', [AdminDashboardController::class, 'scanLogs'])->name('scan-logs');
    Route::post('/race-route', [RaceRouteController::class, 'store'])->name('race-route.store');
    Route::delete('/race-route', [RaceRouteController::class, 'destroy'])->name('race-route.destroy');
    Route::post('/reset-simulation', [AdminDashboardController::class, 'resetSimulation'])->name('reset-simulation');

    // Participant Management
    Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::get('/participants/create', [ParticipantController::class, 'create'])->name('participants.create');
    Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::get('/participants/{participant}/edit', [ParticipantController::class, 'edit'])->name('participants.edit');
    Route::put('/participants/{participant}', [ParticipantController::class, 'update'])->name('participants.update');
    Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
    Route::get('/participants/{participant}/loops', [ParticipantController::class, 'loops'])->name('participants.loops');
    Route::post('/participants/{participant}/rotate-token', [ParticipantController::class, 'rotateToken'])->name('participants.rotate-token');
});
