<?php

use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentFromListController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ScholarshipController::class, 'index'])->name('dashboard');
    Route::post('/subjects/upload', [ScholarshipController::class, 'uploadSubjects']);
    Route::post('/calculate', [ScholarshipController::class, 'calculate']);
    Route::get('/results', [ScholarshipController::class, 'results']);
    Route::get('/results/export', [ScholarshipController::class, 'export']);
    Route::get('/results/{group_name}', [ScholarshipController::class, 'group']);
    Route::post('/results/exclude-grade', [ScholarshipController::class, 'excludeGrade']);
    Route::get("/students/list/create", [StudentFromListController::class, 'create']);
    Route::post("/students/list/store", [StudentFromListController::class, 'store']);
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
