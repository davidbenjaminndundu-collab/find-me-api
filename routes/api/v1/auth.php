<?php

use App\Http\Controllers\Api\V1\Auth\InscriptionController;
use App\Http\Controllers\Api\V1\Auth\VerificationTelephoneController;
use App\Http\Controllers\Api\V1\Auth\ConnexionController;
use App\Http\Controllers\Api\V1\Auth\DemandeRecuperationController;
use App\Http\Controllers\Api\V1\Auth\ReinitialisationMotDePasseController;
// use App\Http\Controllers\Api\V1\Profile\ModificationProfilController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', InscriptionController::class);
Route::post('/auth/verify-phone', VerificationTelephoneController::class);
Route::post('/auth/login', ConnexionController::class);
Route::post(
    '/auth/password/forgot',
    DemandeRecuperationController::class
);

Route::post(
    '/auth/password/reset',
    ReinitialisationMotDePasseController::class
);



// Route::middleware('auth:sanctum')->group(function () {
//     Route::patch(
//         '/profile',
//         ModificationProfilController::class
//     );
// });