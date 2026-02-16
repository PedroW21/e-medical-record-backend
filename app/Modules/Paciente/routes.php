<?php

declare(strict_types=1);

use App\Modules\Paciente\Http\Controllers\AlergiaController;
use App\Modules\Paciente\Http\Controllers\CondicaoCronicaController;
use App\Modules\Paciente\Http\Controllers\EnderecoController;
use App\Modules\Paciente\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/pacientes', [PacienteController::class, 'index']);
    Route::post('/pacientes', [PacienteController::class, 'store']);
    Route::get('/pacientes/{id}', [PacienteController::class, 'show']);
    Route::put('/pacientes/{id}', [PacienteController::class, 'update']);
    Route::delete('/pacientes/{id}', [PacienteController::class, 'destroy']);

    Route::get('/alergias', [AlergiaController::class, 'index']);
    Route::get('/condicoes-cronicas', [CondicaoCronicaController::class, 'index']);

    Route::get('/enderecos/cep/{cep}', [EnderecoController::class, 'buscarPorCep']);
});
