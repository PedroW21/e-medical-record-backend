<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Controllers;

use App\Modules\Patient\Http\Resources\AllergyResource;
use App\Modules\Patient\Models\Alergia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AllergyController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alergia::query()->orderBy('nome');

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        return AllergyResource::collection($query->limit(50)->get());
    }
}
