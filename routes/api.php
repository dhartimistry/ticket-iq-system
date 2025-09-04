<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// API routes for tickets
Route::prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']);
    Route::post('/', [TicketController::class, 'store']);
    Route::get('{id}', [TicketController::class, 'show']);
    Route::patch('{id}', [TicketController::class, 'update']);
    Route::post('{id}/classify', [TicketController::class, 'classify'])->middleware('throttle:30,1');
});

// Stats route
Route::get('stats', [TicketController::class, 'stats']);
