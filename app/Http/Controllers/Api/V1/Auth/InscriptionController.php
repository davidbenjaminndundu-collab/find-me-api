<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\InscriptionRequest;
use App\Services\Auth\InscriptionService;
use Illuminate\Http\JsonResponse;

class InscriptionController extends Controller
{
    public function __invoke(InscriptionRequest $request, InscriptionService $service): JsonResponse
    {
        $utilisateur = $service->inscrire($request);

        return response()->json([
            'message' => 'Compte créé. Vérifiez votre téléphone.',
            'id_utilisateur' => $utilisateur->id_utilisateur,
        ], 201);
    }
}