<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Services\CepService;
use Illuminate\Http\JsonResponse;

final class EnderecoController
{
    public function __construct(
        private readonly CepService $cepService,
    ) {}

    public function buscarPorCep(string $cep): JsonResponse
    {
        $endereco = $this->cepService->buscar($cep);

        return response()->json(['data' => $endereco]);
    }
}
