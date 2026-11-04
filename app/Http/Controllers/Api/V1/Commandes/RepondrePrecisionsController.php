<?php

namespace App\Http\Controllers\Api\V1\Commandes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Commandes\PrecisionsCommandeRequest;
use App\Models\Utilisateur;
use App\Services\Commandes\RepondrePrecisionsService;
use Illuminate\Http\JsonResponse;

class RepondrePrecisionsController extends Controller
{
    public function __construct(
        private readonly RepondrePrecisionsService $service
    ) {
    }

    public function __invoke(
        PrecisionsCommandeRequest $request,
        int $idCommande
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $commande = $this->service->repondre(
            $utilisateur,
            $idCommande,
            $request->validated('description')
        );

        return response()->json([
            'message' =>
                'Précisions enregistrées avec succès.',

            'commande' => [
                'id_commande' =>
                    $commande->id_commande,

                'reference_commande' =>
                    $commande->reference_commande,

                'statut' =>
                    $commande->statut,
            ],
        ]);
    }
}