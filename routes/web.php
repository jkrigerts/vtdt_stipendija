<?php

use App\Http\Controllers\ScholarshipController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScholarshipController::class, 'index']);
Route::post('/subjects/upload', [ScholarshipController::class, 'uploadSubjects']);
Route::post('/calculate', [ScholarshipController::class, 'calculate']);
Route::get('/results', [ScholarshipController::class, 'results']);
Route::get('/results/export', [ScholarshipController::class, 'export']);
Route::post('/results/exclude-grade', [ScholarshipController::class, 'excludeGrade']);
