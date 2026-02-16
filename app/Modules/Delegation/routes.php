<?php

declare(strict_types=1);

use App\Modules\Delegation\Http\Controllers\DelegationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/delegations', [DelegationController::class, 'index']);
    Route::post('/delegations', [DelegationController::class, 'store']);
    Route::delete('/delegations/{id}', [DelegationController::class, 'destroy']);
});
