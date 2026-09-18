<?php

use App\Http\Controllers\Api\V1\Auth\InscriptionController;
use App\Http\Controllers\Api\V1\Auth\VerificationTelephoneController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', InscriptionController::class);
Route::post('/auth/verify-phone', VerificationTelephoneController::class);