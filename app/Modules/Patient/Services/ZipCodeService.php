<?php

declare(strict_types=1);

namespace App\Modules\Patient\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ZipCodeService
{
    /**
     * Look up an address by Brazilian zip code (CEP).
     *
     * @return array{zipCode: string, street: string, neighborhood: string, city: string, state: string}
     *
     * @throws NotFoundHttpException
     */
    public function lookup(string $zip): array
    {
        $cleanZip = preg_replace('/\D/', '', $zip);

        $response = Http::get("https://viacep.com.br/ws/{$cleanZip}/json/");

        if ($response->failed() || $response->json('erro')) {
            throw new NotFoundHttpException('CEP não encontrado.');
        }

        $data = $response->json();

        return [
            'zipCode' => $data['cep'],
            'street' => $data['logradouro'] ?? '',
            'neighborhood' => $data['bairro'] ?? '',
            'city' => $data['localidade'] ?? '',
            'state' => $data['uf'] ?? '',
        ];
    }
}
