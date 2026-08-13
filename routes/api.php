<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'me']);
            Route::put('/update', [AuthController::class, 'update']);
            Route::get('/courses', [CourseController::class, 'getUserCourses']);
            Route::get('/certificates', [CertificateController::class, 'getUserCertificates']);
        });
    });
});


Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'getCourseList']);
    Route::get('/{slug}', [CourseController::class, 'getCourse']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{slug}/start', [CourseController::class, 'start']);
        Route::post('/{slug}/buy', [CourseController::class, 'buy']);
        Route::post('/{slug}/finish', [CourseController::class, 'finish']);
        Route::post('/{slug}/get-certificate', [CertificateController::class, 'generateCertificate']);
    });
});


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/certificates/{id}/download', [CertificateController::class, 'downloadCertificate'])
        ->whereNumber('id')
        ->name('certificates.download');

    Route::prefix('lessons')->group(function () {
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
