<?php

use App\Http\Controllers\Api\V1\Auth\InscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', InscriptionController::class);