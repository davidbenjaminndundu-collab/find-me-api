<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ModificationProfilRequest;
use App\Models\Utilisateur;
use App\Services\Profile\ModificationProfilService;
use Illuminate\Http\JsonResponse;

class ModificationProfilController extends Controller
{
    public function __construct(
        private readonly ModificationProfilService $service
    ) {
    }

    public function __invoke(
        ModificationProfilRequest $request
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $utilisateur = $this->service->modifier(
            $utilisateur,
            $request->validated()
        );

        return response()->json([
            'message' => 'Profil modifié avec succès.',
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