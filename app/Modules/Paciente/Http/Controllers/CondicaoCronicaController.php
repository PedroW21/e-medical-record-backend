<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Http\Resources\CondicaoCronicaResource;
use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CondicaoCronicaController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CondicaoCronica::query()->orderBy('nome');

        if ($request->filled('busca')) {
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower((string) $request->string('busca')).'%']);
        }

        return CondicaoCronicaResource::collection($query->limit(50)->get());
    }
}
