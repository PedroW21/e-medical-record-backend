<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CepService
{
    /**
     * @return array{cep: string, logradouro: string, bairro: string, cidade: string, estado: string}
     *
     * @throws NotFoundHttpException
     */
    public function buscar(string $cep): array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep);

        $response = Http::get("https://viacep.com.br/ws/{$cepLimpo}/json/");

        if ($response->failed() || $response->json('erro')) {
            throw new NotFoundHttpException('CEP não encontrado.');
        }

        $data = $response->json();

        return [
            'cep' => $data['cep'],
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['localidade'] ?? '',
            'estado' => $data['uf'] ?? '',
        ];
    }
}
