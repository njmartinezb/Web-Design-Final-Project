<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JustificationController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta dashboard protegida por auth, verified y ahora también por rol
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:user,admin,professor'])
    ->name('dashboard');

    Route::get('/justifications/available-classes', [JustificationController::class, 'getAvailableClasses'])
    ->name('justifications.available-classes')
        ->middleware(['auth']);

// Rutas de aprobación/rechazo de justificaciones (solo admin)
Route::middleware(['auth', 'role:admin,professor'])->group(function () {
    Route::post('/justifications/{justification}/approve', [DashboardController::class, 'approve'])
        ->name('justifications.approve');
    Route::post('/justifications/{justification}/reject', [DashboardController::class, 'reject'])
        ->name('justifications.reject');
    Route::resource('professors', ProfessorController::class);
    Route::resource('classes', ClassController::class);
    Route::resource('faculties', FacultyController::class);
});

// Grupo de rutas de perfil - ahora verificamos rol también
Route::middleware(['auth', 'role:user,admin,professor'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('/about', 'pages.about')->name('about');
    Route::resource('justifications', JustificationController::class);


    Route::get('/justifications/{justification}/download/{document}', [JustificationController::class, 'downloadDocument'])
        ->name('justifications.download')
        ->middleware(['auth']);


Route::get('/classes/{class}/details', [ClassController::class, 'details'])
    ->name('classes.details');

    Route::view('/about', 'pages.about')
    ->name('about');
    Route::get('/dashboard/justifications/{justification}', [DashboardController::class, 'showJustification'])
        ->name('dashboard.justifications.show');
    Route::patch(
      '/justifications/{justification}/status',
      [JustificationController::class, 'updateStatus']
    )->name('justifications.updateStatus');
});

require __DIR__.'/auth.php';
