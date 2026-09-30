<?php
use App\Http\Controllers\Api\V1\Administration\RefuserAnnonceController;
use App\Http\Controllers\Api\V1\Administration\ValiderAnnonceController;
use App\Http\Controllers\Api\V1\Administration\AdminUtilisateurController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/users/{id}', AdminUtilisateurController::class);
    Route::patch(
        '/admin/annonces/{idAnnonce}/valider',
        ValiderAnnonceController::class
    )->whereNumber('idAnnonce');

    Route::patch(
        '/admin/annonces/{idAnnonce}/refuser',
        RefuserAnnonceController::class
    )->whereNumber('idAnnonce');
});