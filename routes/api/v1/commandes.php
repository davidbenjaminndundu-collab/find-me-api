<?php

use App\Http\Controllers\Api\V1\Commandes\CreerDemandeController;
use App\Http\Controllers\Api\V1\Commandes\ActionCommandeController;
use App\Http\Controllers\Api\V1\Commandes\RepondrePrecisionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/commandes',
        CreerDemandeController::class
    );

    
    Route::post(
        '/commandes/{idCommande}/actions',
        ActionCommandeController::class
    )->whereNumber('idCommande');

    Route::post(
        '/commandes/{idCommande}/precisions',
        RepondrePrecisionsController::class
    )->whereNumber('idCommande');

});