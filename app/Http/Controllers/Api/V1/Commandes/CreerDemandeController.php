<?php

namespace App\Http\Controllers\Api\V1\Commandes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Commandes\CreerDemandeRequest;
use App\Models\Utilisateur;
use App\Services\Commandes\CreerDemandeService;
use Illuminate\Http\JsonResponse;

class CreerDemandeController extends Controller
{
    public function __construct(
        private readonly CreerDemandeService $service
    ) {
    }

    public function __invoke(
        CreerDemandeRequest $request
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $commande = $this->service->creer(
            $utilisateur,
            $request->validated()
        );

        return response()->json([
            'message' => 'Demande envoyée avec succès.',

            'commande' => [
                'id_commande' =>
                    $commande->id_commande,

                'reference_commande' =>
                    $commande->reference_commande,

                'id_annonce' =>
                    $commande->id_annonce,

                'titre_service' =>
                    $commande->titre_service,

                'description_commande' =>
                    $commande->description_commande,

                'montant_total' =>
                    $commande->montant_total,

                'delai_livraison_jours' =>
                    $commande->delai_livraison_jours,

                'nombre_revisions_incluses' =>
                    $commande->nombre_revisions_incluses,

                'livrables_convenus' =>
                    $commande->livrables_convenus,

                'statut' =>
                    $commande->statut,

                'est_offre_personnalisee' =>
                    $commande->est_offre_personnalisee,
            ],
        ], 201);
    }
}