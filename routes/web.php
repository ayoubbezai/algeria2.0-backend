<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'platform' => 'CASEFILE 404 — Digital Forensics Investigation Platform',
        'division' => 'Digital Forensics & Incident Response Division',
        'event' => 'Algeria 2.0 Workshop Edition',
        'status' => 'ONLINE',
        'api_endpoints' => [
            'health' => '/api/health',
            'auth_login' => 'POST /api/auth/login',
            'case_files' => 'GET /api/case?id={id}&fix={bool}',
            'evidence_fetch' => 'POST /api/evidence/fetch',
        ],
    ]);
});
