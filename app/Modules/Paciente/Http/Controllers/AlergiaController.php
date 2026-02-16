<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Http\Resources\AlergiaResource;
use App\Modules\Paciente\Models\Alergia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AlergiaController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alergia::query()->orderBy('nome');

        if ($request->filled('busca')) {
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower((string) $request->string('busca')).'%']);
        }

        return AlergiaResource::collection($query->limit(50)->get());
    }
}
