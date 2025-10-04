<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\PreferenceController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
Route::get('/articles/filters', [ArticleController::class, 'filters']);

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user()
        ]);
    });
    
    // User preferences endpoints
    Route::get('/preferences', [PreferenceController::class, 'show']);
    Route::post('/preferences', [PreferenceController::class, 'store']);
    Route::put('/preferences', [PreferenceController::class, 'update']);
    Route::delete('/preferences', [PreferenceController::class, 'destroy']);
    
    // Personalized feed
    Route::get('/articles/personalized/feed', [ArticleController::class, 'personalizedFeed']);
});
