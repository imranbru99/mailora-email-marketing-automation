<?php

use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::post('/feedback/{endpoint}', FeedbackController::class)->middleware('throttle:120,1')->name('feedback.receive');
