<?php

use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ScholarshipController::class, 'index'])->name('dashboard');
    Route::post('/subjects/upload', [ScholarshipController::class, 'uploadSubjects']);
    Route::post('/calculate', [ScholarshipController::class, 'calculate']);
    Route::get('/results', [ScholarshipController::class, 'results']);
    Route::get('/results/export', [ScholarshipController::class, 'export']);
    Route::post('/results/exclude-grade', [ScholarshipController::class, 'excludeGrade']);
});


Route::get('/', function () {
    return view('auth.login');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
