<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\DemandeRecuperationRequest;
use App\Services\Auth\RecuperationCompteService;
use Illuminate\Http\JsonResponse;

class DemandeRecuperationController extends Controller
{
    public function __construct(
        private readonly RecuperationCompteService $recuperationCompteService
    ) {
    }

    public function __invoke(
        DemandeRecuperationRequest $request
    ): JsonResponse {
        $this->recuperationCompteService->demanderCode(
            $request->validated('telephone')
        );

        return response()->json([
            'message' =>
                'Si ce numéro correspond à un compte, ' .
                'un code de récupération a été envoyé.',
        ]);
    }
}