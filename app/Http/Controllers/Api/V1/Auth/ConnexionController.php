<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ConnexionRequest;
use App\Services\Auth\ConnexionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ConnexionController extends Controller
{
    public function __construct(
        private readonly ConnexionService $connexionService
    ) {
    }

    public function __invoke(ConnexionRequest $request): JsonResponse
    {
        $utilisateur = $this->connexionService->connecter(
            $request->validated()
        );

        Auth::login($utilisateur);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Connexion réussie.',
            'utilisateur' => [
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'telephone' => $utilisateur->telephone,
                'nom' => $utilisateur->nom,
                'prenom' => $utilisateur->prenom,
                'postnom' => $utilisateur->postnom,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'statut_compte' => $utilisateur->statut_compte,
            ],
        ]);
    }
}