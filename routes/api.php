<?php

use App\Http\Controllers\Api\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [ArticleController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/articles/personalized', [ArticleController::class, 'index']);
});