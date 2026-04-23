<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\InjectableCatalogController;
use App\Modules\Catalog\Http\Controllers\InjectableProtocolCatalogController;
use App\Modules\Catalog\Http\Controllers\MagistralCatalogController;
use App\Modules\Catalog\Http\Controllers\PharmacyCatalogController;
use App\Modules\Catalog\Http\Controllers\ProblemListCatalogController;
use App\Modules\Catalog\Http\Controllers\TherapeuticCategoryCatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('catalog')->group(function (): void {
    Route::get('/pharmacies', [PharmacyCatalogController::class, 'index']);
    Route::get('/therapeutic-categories', [TherapeuticCategoryCatalogController::class, 'index']);

    Route::get('/magistral/categories', [MagistralCatalogController::class, 'categories']);
    Route::get('/magistral/formulas', [MagistralCatalogController::class, 'formulas']);

    Route::get('/injectables', [InjectableCatalogController::class, 'index']);
    Route::get('/injectables/{id}', [InjectableCatalogController::class, 'show']);

    Route::get('/injectable-protocols', [InjectableProtocolCatalogController::class, 'index']);
    Route::get('/injectable-protocols/{id}', [InjectableProtocolCatalogController::class, 'show']);

    Route::get('/problem-list', [ProblemListCatalogController::class, 'index']);
});
