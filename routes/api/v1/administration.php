<?php

use App\Http\Controllers\Api\V1\Administration\AdminUtilisateurController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/users/{id}', AdminUtilisateurController::class);
});