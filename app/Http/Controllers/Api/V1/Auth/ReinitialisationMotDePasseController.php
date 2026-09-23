<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ReinitialisationMotDePasseRequest;
use App\Services\Auth\RecuperationCompteService;
use Illuminate\Http\JsonResponse;

class ReinitialisationMotDePasseController extends Controller
{
    public function __construct(
        private readonly RecuperationCompteService $recuperationCompteService
    ) {
    }

    public function __invoke(
        ReinitialisationMotDePasseRequest $request
    ): JsonResponse {
        $donnees = $request->validated();

        $this->recuperationCompteService
            ->reinitialiserMotDePasse(
                $donnees['telephone'],
                $donnees['code_otp'],
                $donnees['mot_de_passe']
            );

        return response()->json([
            'message' =>
                'Mot de passe réinitialisé avec succès. ' .
                'Vous pouvez maintenant vous connecter.',
        ]);
    }
}