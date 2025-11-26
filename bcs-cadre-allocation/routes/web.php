<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ContentController;
use App\Http\Controllers\AllocationController;

Route::get('/', [ContentController::class, 'index']);
Route::get('/candidates', [ContentController::class, 'candidates']);
Route::get('/allocations', [ContentController::class, 'allocations']);
Route::get('/print-allocation', [ContentController::class, 'allocations_print']);
Route::get('/allocation/run', [AllocationController::class, 'runAllocation']);
