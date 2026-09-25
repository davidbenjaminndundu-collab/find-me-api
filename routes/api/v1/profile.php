<?php

use App\Http\Controllers\Api\V1\Profile\ModificationProfilController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch(
        '/profile',
        ModificationProfilController::class
    );
});