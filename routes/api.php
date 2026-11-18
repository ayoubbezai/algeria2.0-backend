<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseFileController;
use App\Http\Controllers\EvidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CASEFILE 404 - Digital Forensics API Routes
|--------------------------------------------------------------------------
*/

// Health & System status
Route::get('/health', function () {
    return response()->json([
        'status' => 'operational',
        'service' => 'CASEFILE 404 Forensics Bureau API',
        'incident' => 'CASE #404 — ACTIVE',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authentication & SQL Injection Demonstration
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Case Files Management & IDOR Demonstration
Route::get('/cases', [CaseFileController::class, 'index']);
Route::get('/case', [CaseFileController::class, 'show']); // Matches frontend GET /case?id=1042
Route::get('/cases/show', [CaseFileController::class, 'show']);

// Evidence Management & SSRF Demonstration
Route::get('/evidence', [EvidenceController::class, 'index']);
Route::match(['get', 'post'], '/evidence/fetch', [EvidenceController::class, 'fetch']);
