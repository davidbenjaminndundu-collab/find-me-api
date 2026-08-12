<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v0')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API Find Me operationnelle',
            'service' => 'find-me-api',
            'version' => '1.0.0',
            'environment' => app()->environment(),
        ]);
    });
});