<?php

use App\Http\Controllers\Api\V1\Annonces\CreerAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\PublierAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\DesactiverAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\ConsulterAnnoncePubliqueController;
use App\Http\Controllers\Api\V1\Annonces\ModifierAnnonceController;
use App\Http\Controllers\Api\V1\Annonces\RechercheAnnonceController;
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

    Route::patch('/annonces/{id}', ModifierAnnonceController::class);
});

Route::get(
    '/annonces',
    RechercheAnnonceController::class
);

Route::get(
    '/annonces/{idAnnonce}',
    ConsulterAnnoncePubliqueController::class
)->whereNumber('idAnnonce');