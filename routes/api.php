<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant', 'throttle:60,1', 'module:api'])->group(function () {
    Route::get('contacts', [ApiController::class, 'contacts'])->middleware('module:contacts');
    Route::post('contacts', [ApiController::class, 'storeContact'])->middleware('module:contacts');
    Route::post('events', [ApiController::class, 'event'])->middleware('module:events');
});
