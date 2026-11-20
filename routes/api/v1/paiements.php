<?php

use App\Http\Controllers\Api\V1\Paiements\RecevoirWebhookPaiementController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/webhooks/paiements',
    RecevoirWebhookPaiementController::class
);