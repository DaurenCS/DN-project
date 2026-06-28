<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

// Импортируем новый контроллер тестов

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

    Route::group(['prefix' => 'lessons'], function () {
        Route::get('/{slug}', [LessonController::class, 'getLesson']);
        Route::post('/{slug}/finish', [LessonController::class, 'finishLesson']);
    });
    Route::prefix('tests/{test}')->group(function () {
        Route::get('/', [TestController::class, 'show']);
        Route::post('/questions/{question}/answer', [TestController::class, 'saveAnswer']);
        Route::post('/submit', [TestController::class, 'submit']);
        Route::get('/results', [TestController::class, 'getResults']);
    });
});
Route::group(['prefix' => 'courses'], function () {
    Route::get('/', [CourseController::class, 'getCourseList']);
    Route::get('{slug}', [CourseController::class, 'getCourse']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('{slug}/start', [CourseController::class, 'start']);
        Route::post('{slug}/buy', [CourseController::class, 'buy']);
        Route::post('{slug}/finish', [CourseController::class, 'finish']);
        Route::post('{slug}/get-certificate', [CourseController::class, 'generateCertificate']);
    });
});
