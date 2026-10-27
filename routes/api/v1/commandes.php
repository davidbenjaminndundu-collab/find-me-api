<?php

use App\Http\Controllers\Api\V1\Commandes\CreerDemandeController;
use App\Http\Controllers\Api\V1\Commandes\AccepterDemandeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/commandes',
        CreerDemandeController::class
    );

    Route::patch(
        '/commandes/{idCommande}/accepter',
        AccepterDemandeController::class
    )->whereNumber('idCommande');
});