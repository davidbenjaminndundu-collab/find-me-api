<?php

use App\Http\Controllers\Api\V1\Prestataire\ProfilPrestataireController;
use App\Http\Controllers\Api\V1\Prestataire\ConsultationProfilPrestataireController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::put(
        '/prestataire/profil',
        ProfilPrestataireController::class
    );
});

Route::get(
    '/prestataires/{idPrestataire}',
    ConsultationProfilPrestataireController::class
)->whereNumber('idPrestataire');
