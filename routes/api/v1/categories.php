<?php

use App\Http\Controllers\Api\V1\Categories\ListerCategoriesController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/categories',
    ListerCategoriesController::class
);