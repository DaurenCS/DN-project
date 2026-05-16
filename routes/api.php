<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::group(['prefix' => 'profile'], function () {
            Route::get('/', [AuthController::class, 'me']);
            Route::put('/update', [AuthController::class, 'update']);
            Route::get('/courses', [CourseController::class, 'getUserCourses']);
        });

    });
});
Route::middleware('auth:sanctum')->group(function () {

});
Route::group(['prefix' => 'courses'], function () {
        Route::get('/', [CourseController::class, 'getCourseList']);
        Route::get('{slug}', [CourseController::class, 'getCourse']);
});

