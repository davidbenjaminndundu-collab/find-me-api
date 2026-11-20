<?php

use App\Http\Controllers\Api\V1\Categories\CreerCategorieController;
use App\Http\Controllers\Api\V1\Categories\ListerCategoriesController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/categories',
    ListerCategoriesController::class
);

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/admin/categories',
        CreerCategorieController::class
    );
});