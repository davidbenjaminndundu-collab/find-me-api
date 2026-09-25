<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Administration\ModificationStatutCompteRequest;
use App\Models\Utilisateur;
use App\Services\Administration\AdminUtilisateurService;
use Illuminate\Http\JsonResponse;

class AdminUtilisateurController extends Controller
{
    public function __construct(
        private readonly AdminUtilisateurService $service
    ) {}

    public function __invoke(ModificationStatutCompteRequest $request, int $id): JsonResponse
    {
        /** @var Utilisateur $admin */
        $admin = $request->user();

        $utilisateur = $this->service->modifierStatut(
            $admin,
            $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Statut du compte mis a jour.',
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