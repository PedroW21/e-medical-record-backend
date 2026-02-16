<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Actions\CreatePacienteAction;
use App\Modules\Paciente\Actions\DeletePacienteAction;
use App\Modules\Paciente\Actions\UpdatePacienteAction;
use App\Modules\Paciente\DTOs\CreatePacienteDTO;
use App\Modules\Paciente\DTOs\UpdatePacienteDTO;
use App\Modules\Paciente\Http\Requests\ListPacienteRequest;
use App\Modules\Paciente\Http\Requests\StorePacienteRequest;
use App\Modules\Paciente\Http\Requests\UpdatePacienteRequest;
use App\Modules\Paciente\Http\Resources\PacienteListResource;
use App\Modules\Paciente\Http\Resources\PacienteResource;
use App\Modules\Paciente\Services\PacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PacienteController
{
    public function __construct(
        private readonly PacienteService $pacienteService,
        private readonly CreatePacienteAction $createAction,
        private readonly UpdatePacienteAction $updateAction,
        private readonly DeletePacienteAction $deleteAction,
    ) {}

    public function index(ListPacienteRequest $request): AnonymousResourceCollection
    {
        $pacientes = $this->pacienteService->listForUser(
            userId: $request->user()->id,
            filters: $request->validated(),
        );

        return PacienteListResource::collection($pacientes);
    }

    public function store(StorePacienteRequest $request): JsonResponse
    {
        $dto = CreatePacienteDTO::fromRequest($request);

        $paciente = $this->createAction->execute(
            userId: $request->user()->id,
            dto: $dto,
        );

        return (new PacienteResource($paciente))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id): PacienteResource
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('view', $paciente);

        return new PacienteResource($paciente);
    }

    public function update(UpdatePacienteRequest $request, int $id): PacienteResource
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('update', $paciente);

        $dto = UpdatePacienteDTO::fromRequest($request);
        $paciente = $this->updateAction->execute($paciente, $dto);

        return new PacienteResource($paciente);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('delete', $paciente);

        $this->deleteAction->execute($paciente);

        return response()->json(['message' => 'Paciente excluído com sucesso.']);
    }
}
