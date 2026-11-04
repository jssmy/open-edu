<?php

use App\Modules\Assessments\Infrastructure\Http\AssessmentController;
use App\Modules\Courses\Infrastructure\Http\ContentController;
use App\Modules\Courses\Infrastructure\Http\ContentImageController;
use App\Modules\Courses\Infrastructure\Http\CourseController;
use App\Modules\Courses\Infrastructure\Http\CourseCoverController;
use App\Modules\Enrollments\Infrastructure\Http\CourseEnrollmentController;
use App\Modules\Enrollments\Infrastructure\Http\EnrollmentController;
use App\Modules\Identity\Infrastructure\Http\AuthController;
use App\Modules\Identity\Infrastructure\Http\PasswordController;
use App\Modules\Identity\Infrastructure\Http\RoleController;
use App\Modules\Identity\Infrastructure\Http\UserController;
use App\Modules\Progress\Infrastructure\Http\StudyController;
use App\Modules\Reports\Infrastructure\Http\ReportController;
use App\Modules\Scheduling\Infrastructure\Http\ClassroomController;
use App\Modules\Scheduling\Infrastructure\Http\PeriodController;
use App\Modules\Scheduling\Infrastructure\Http\SchedulingController;
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

    Route::middleware('can:courses.manage')->group(function () {
        Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    });

    Route::middleware('can:courses.view')->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    });

    Route::middleware('can:courses.manage')->group(function () {
        Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    });

    Route::middleware('can:courses.manage')->group(function () {
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::patch('/courses/{course}/status', [CourseController::class, 'status'])->name('courses.status');
    });

    Route::middleware('can:content.manage')->group(function () {
        Route::get('/units/{unit}/lessons/create', [ContentController::class, 'createLesson'])
            ->name('lessons.create');
        Route::get('/lessons/{lesson}/edit', [ContentController::class, 'editLesson'])
            ->name('lessons.edit');
        Route::post('/courses/{course}/content-images', [ContentImageController::class, 'store'])
            ->name('course-images.store');
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

    Route::get('/courses/{course}/cover', [CourseCoverController::class, 'show'])->name('courses.cover');
    Route::get('/courses/{course}/content-images/{image}', [ContentImageController::class, 'show'])
        ->name('course-images.show');

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

    Route::middleware('can:study')->group(function () {
        Route::get('/my/courses', [StudyController::class, 'index'])->name('study.courses.index');
        Route::get('/my/courses/{course}', [StudyController::class, 'show'])->name('study.courses.show');
        Route::post('/lessons/{lesson}/complete', [StudyController::class, 'complete'])->name('study.lessons.complete');
        Route::get('/lessons/{lesson}/content', [StudyController::class, 'content'])->name('study.lessons.content');
        Route::get('/my/results', [AssessmentController::class, 'myResults'])->name('assessments.my-results');
        Route::get('/my/progress', [ReportController::class, 'myProgress'])->name('progress.my');
    });

    Route::get('/courses/{course}/progress', [ReportController::class, 'courseProgress'])
        ->name('progress.course');

    Route::middleware('can:reports.view')->group(function () {
        Route::get('/reports/enrollments', [ReportController::class, 'enrollments'])->name('reports.enrollments');
        Route::get('/reports/progress', [ReportController::class, 'progress'])->name('reports.progress');
        Route::get('/reports/results', [ReportController::class, 'results'])->name('reports.results');
    });

    Route::middleware('can:scheduling.manage')->group(function () {
        Route::get('/scheduling', [SchedulingController::class, 'index'])->name('scheduling.index');
        Route::get('/scheduling/pending', [SchedulingController::class, 'pending'])->name('scheduling.pending');
        Route::post('/scheduling/schedules', [SchedulingController::class, 'store'])->name('scheduling.store');
        Route::put('/scheduling/schedules/{schedule}', [SchedulingController::class, 'update'])->name('scheduling.update');
        Route::delete('/scheduling/schedules/{schedule}', [SchedulingController::class, 'destroy'])->name('scheduling.destroy');

        Route::get('/scheduling/classrooms', [ClassroomController::class, 'index'])->name('scheduling.classrooms.index');
        Route::post('/scheduling/classrooms', [ClassroomController::class, 'store'])->name('scheduling.classrooms.store');
        Route::put('/scheduling/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('scheduling.classrooms.update');
        Route::delete('/scheduling/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('scheduling.classrooms.destroy');

        Route::get('/scheduling/periods', [PeriodController::class, 'index'])->name('scheduling.periods.index');
        Route::post('/scheduling/periods', [PeriodController::class, 'store'])->name('scheduling.periods.store');
        Route::put('/scheduling/periods/{period}', [PeriodController::class, 'update'])->name('scheduling.periods.update');
        Route::delete('/scheduling/periods/{period}', [PeriodController::class, 'destroy'])->name('scheduling.periods.destroy');
    });

    Route::middleware('can:scheduling.view-own')->group(function () {
        Route::get('/my/schedule', [SchedulingController::class, 'mySchedule'])->name('scheduling.my');
    });

    Route::get('/courses/{course}/assessments/create', [AssessmentController::class, 'create'])
        ->name('assessments.create');
    Route::post('/courses/{course}/assessments', [AssessmentController::class, 'store'])
        ->name('assessments.store');
    Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])
        ->name('assessments.edit');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])
        ->name('assessments.update');
    Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])
        ->name('assessments.destroy');
    Route::patch('/assessments/{assessment}/publish', [AssessmentController::class, 'publish'])
        ->name('assessments.publish');
    Route::get('/assessments/{assessment}/take', [AssessmentController::class, 'take'])
        ->name('assessments.take');
    Route::post('/assessments/{assessment}/attempts', [AssessmentController::class, 'submit'])
        ->name('assessments.attempts.store');
    Route::get('/assessments/{assessment}/results', [AssessmentController::class, 'results'])
        ->name('assessments.results');
});
