<?php

use App\Http\Controllers\Api\V1\Annonces\CreerAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\PublierAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\DesactiverAnnonceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/annonces', CreerAnnonceController::class);

    Route::patch(
        '/annonces/{idAnnonce}/publier',
        PublierAnnonceController::class
    )->whereNumber('idAnnonce');


    Route::patch(
        '/annonces/{idAnnonce}/desactiver',
        DesactiverAnnonceController::class
    )->whereNumber('idAnnonce');
});

