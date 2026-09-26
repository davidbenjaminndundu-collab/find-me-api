<?php

use App\Http\Controllers\Api\V1\Auth\InscriptionController;
use App\Http\Controllers\Api\V1\Auth\VerificationTelephoneController;
use App\Http\Controllers\Api\V1\Auth\ConnexionController;
use App\Http\Controllers\Api\V1\Auth\DemandeRecuperationController;
use App\Http\Controllers\Api\V1\Auth\ReinitialisationMotDePasseController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', InscriptionController::class);
Route::post(
    '/auth/verify-phone',
    VerificationTelephoneController::class
)->middleware('throttle:otp_verification');

Route::post(
    '/auth/login',
    ConnexionController::class
)->middleware('throttle:connexion');

Route::post(
    '/auth/password/forgot',
    DemandeRecuperationController::class
)->middleware('throttle:otp_envoi');

Route::post(
    '/auth/password/reset',
    ReinitialisationMotDePasseController::class
)->middleware('throttle:otp_verification');



