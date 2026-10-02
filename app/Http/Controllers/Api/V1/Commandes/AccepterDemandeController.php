<?php

namespace App\Http\Controllers\Api\V1\Commandes;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\Commandes\AccepterDemandeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccepterDemandeController extends Controller
{
    public function __construct(
        private readonly AccepterDemandeService $service
    ) {
    }

    public function __invoke(
        Request $request,
        int $idCommande
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $commande = $this->service->accepter(
            $utilisateur,
            $idCommande
        );

        return response()->json([
            'message' =>
                'Demande acceptée avec succès.',

            'commande' => [
                'id_commande' =>
                    $commande->id_commande,

                'reference_commande' =>
                    $commande->reference_commande,

                'statut' =>
                    $commande->statut,

                'date_acceptation' =>
                    $commande->date_acceptation,

                'montant_total' =>
                    $commande->montant_total,

                'delai_livraison_jours' =>
                    $commande->delai_livraison_jours,

                'nombre_revisions_incluses' =>
                    $commande->nombre_revisions_incluses,

                'livrables_convenus' =>
                    $commande->livrables_convenus,
            ],
        ]);
    }
}