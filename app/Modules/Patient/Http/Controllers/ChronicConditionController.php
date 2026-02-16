<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Controllers;

use App\Modules\Patient\Http\Resources\ChronicConditionResource;
use App\Modules\Patient\Models\CondicaoCronica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ChronicConditionController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CondicaoCronica::query()->orderBy('nome');

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        return ChronicConditionResource::collection($query->limit(50)->get());
    }
}
