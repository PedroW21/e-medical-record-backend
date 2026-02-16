<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Controllers;

use App\Modules\Patient\Services\ZipCodeService;
use Illuminate\Http\JsonResponse;

final class AddressController
{
    public function __construct(
        private readonly ZipCodeService $zipCodeService,
    ) {}

    public function lookupByZip(string $zip): JsonResponse
    {
        $address = $this->zipCodeService->lookup($zip);

        return response()->json(['data' => $address]);
    }
}
