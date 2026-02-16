<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Controllers;

use App\Modules\Delegation\Http\Requests\StoreDelegationRequest;
use App\Modules\Delegation\Http\Resources\DelegationResource;
use App\Modules\Delegation\Models\Delegacao;
use App\Modules\Delegation\Services\DelegationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class DelegationController
{
    public function __construct(
        private readonly DelegationService $delegationService,
    ) {}

    /**
     * List all delegations for the authenticated user.
     *
     * @authenticated
     *
     * @group Delegations
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $delegations = $this->delegationService->listForUser($request->user());

        return DelegationResource::collection($delegations);
    }

    /**
     * Create a new delegation.
     *
     * @authenticated
     *
     * @group Delegations
     */
    public function store(StoreDelegationRequest $request): JsonResponse
    {
        Gate::authorize('create', Delegacao::class);

        $delegation = Delegacao::query()->create([
            'medico_id' => $request->user()->id,
            'secretaria_id' => $request->validated('secretary_id'),
        ]);

        $delegation->load(['medico', 'secretaria']);

        return (new DelegationResource($delegation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a delegation.
     *
     * @authenticated
     *
     * @group Delegations
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $delegation = Delegacao::query()->findOrFail($id);

        Gate::authorize('delete', $delegation);

        $delegation->delete();

        return response()->json(['message' => 'Delegação removida com sucesso.']);
    }
}
