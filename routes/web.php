<?php

use App\Modules\Courses\Infrastructure\Http\ContentController;
use App\Modules\Courses\Infrastructure\Http\CourseController;
use App\Modules\Enrollments\Infrastructure\Http\CourseEnrollmentController;
use App\Modules\Enrollments\Infrastructure\Http\EnrollmentController;
use App\Modules\Identity\Infrastructure\Http\AuthController;
use App\Modules\Identity\Infrastructure\Http\PasswordController;
use App\Modules\Identity\Infrastructure\Http\RoleController;
use App\Modules\Identity\Infrastructure\Http\UserController;
use App\Modules\Students\Infrastructure\Http\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/forgot-password', [PasswordController::class, 'forgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/password/change', [PasswordController::class, 'change'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'updateOwn'])->name('password.change.update');
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::patch('/users/{id}/status', [UserController::class, 'status']);
    });

    Route::middleware('can:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::put('/roles/{id}/permissions', [RoleController::class, 'update']);
    });

    Route::middleware('can:courses.view')->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    });

    Route::middleware('can:courses.manage')->group(function () {
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::patch('/courses/{course}/status', [CourseController::class, 'status'])->name('courses.status');
        Route::put('/courses/{course}/teachers', [CourseController::class, 'teachers'])->name('courses.teachers');
    });

    Route::middleware('can:content.manage')->group(function () {
        Route::post('/courses/{courseId}/units', [ContentController::class, 'storeUnit'])->name('units.store');
        Route::patch('/units/reorder', [ContentController::class, 'reorderUnits'])->name('units.reorder');
        Route::put('/units/{unit}', [ContentController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units/{unit}', [ContentController::class, 'deleteUnit'])->name('units.destroy');
        Route::post('/units/{unit}/lessons', [ContentController::class, 'storeLesson'])->name('lessons.store');
        Route::patch('/lessons/reorder', [ContentController::class, 'reorderLessons'])->name('lessons.reorder');
        Route::put('/lessons/{lesson}', [ContentController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [ContentController::class, 'deleteLesson'])->name('lessons.destroy');
        Route::patch('/lessons/{lesson}/archive', [ContentController::class, 'archiveLesson'])->name('lessons.archive');
    });

    Route::middleware('can:students.view')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{studentId}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{studentId}/courses', [StudentController::class, 'courses'])->name('students.courses');
        Route::get('/courses/{courseId}/students', [CourseEnrollmentController::class, 'students'])->name('courses.students');
    });

    Route::middleware('can:students.manage')->group(function () {
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{studentId}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('/students/{studentId}/status', [StudentController::class, 'status'])->name('students.status');
    });

    Route::middleware('can:enrollments.manage')->group(function () {
        Route::post('/courses/{courseId}/enrollments', [CourseEnrollmentController::class, 'store'])->name('enrollments.store');
        Route::patch('/enrollments/{enrollment}/status', [EnrollmentController::class, 'status'])->name('enrollments.status');
    });
});
