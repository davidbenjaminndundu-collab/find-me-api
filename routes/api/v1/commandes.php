<?php

use App\Http\Controllers\Api\V1\Commandes\CreerDemandeController;
use App\Http\Controllers\Api\V1\Commandes\AccepterDemandeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/commandes',
        CreerDemandeController::class
    );

    
    Route::post(
        '/commandes/{idCommande}/actions',
        AccepterDemandeController::class
    )->whereNumber('idCommande');

});