<?php

use App\Http\Controllers\Api\RfidScanController;
use Illuminate\Support\Facades\Route;

Route::get('/participants/by-card/{uid}', [RfidScanController::class, 'lookup']);
Route::post('/rfid/scan', [RfidScanController::class, 'scan']);
Route::get('/participants/{participant}/loops', [RfidScanController::class, 'loops']);
Route::get('/dashboard/summary', [RfidScanController::class, 'summary']);
Route::get('/dashboard/recent-scans', [RfidScanController::class, 'recentScans']);
Route::get('/dashboard/participants', [RfidScanController::class, 'participants']);
Route::get('/dashboard/scans', [RfidScanController::class, 'scans']);
