<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerificationTelephoneRequest;
use App\Services\Auth\VerificationTelephoneService;
use Illuminate\Http\JsonResponse;

class VerificationTelephoneController extends Controller
{
    public function __invoke(
        VerificationTelephoneRequest $request,
        VerificationTelephoneService $service
    ): JsonResponse {
        $utilisateur = $service->verifier($request);

        return response()->json([
            'message' => 'Compte active. Session ouverte.',
            'utilisateur' => $utilisateur,
        ]);
    }
}