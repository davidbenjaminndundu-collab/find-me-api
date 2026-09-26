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

Route::prefix('v1')-> group(function(){
    require __DIR__ .'/api/v1/auth.php';

    require __DIR__ .'/api/v1/profile.php';
    require __DIR__.'/api/v1/administration.php';
    require __DIR__.'/api/v1/annonces.php';
});