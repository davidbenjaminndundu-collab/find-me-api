<?php

use App\Http\Controllers\Api\V1\Commandes\CreerDemandeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/commandes',
        CreerDemandeController::class
    );
});