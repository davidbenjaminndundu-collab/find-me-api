<?php

use App\Http\Controllers\Api\V1\Annonces\CreerAnnonceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/annonces', CreerAnnonceController::class);
});