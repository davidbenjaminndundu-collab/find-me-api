<?php

namespace App\Http\Controllers\Api\V1\Commandes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Commandes\CommandeActionRequest;
use App\Models\Utilisateur;
use App\Services\Commandes\AccepterDemandeService;
use App\Services\Commandes\RefuserDemandeService;
use Illuminate\Http\JsonResponse;

class ActionCommandeController extends Controller
{
    public function __construct(
        private readonly AccepterDemandeService $accepterService,
        private readonly RefuserDemandeService $refuserService
    ) {
    }

    public function __invoke(
        CommandeActionRequest $request,
        int $idCommande
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $donnees = $request->validated();

        $commande = match ($donnees['type_action']) {
            'accepter' =>
                $this->accepterService->accepter(
                    $utilisateur,
                    $idCommande
                ),

            'refuser' =>
                $this->refuserService->refuser(
                    $utilisateur,
                    $idCommande,
                    $donnees['motif']
                ),
        };

        $message = match ($donnees['type_action']) {
            'accepter' =>
                'Demande acceptée avec succès.',

            'refuser' =>
                'Demande refusée avec succès.',
        };

        return response()->json([
            'message' => $message,

            'commande' => [
                'id_commande' =>
                    $commande->id_commande,

                'reference_commande' =>
                    $commande->reference_commande,

                'statut' =>
                    $commande->statut,

                'date_acceptation' =>
                    $commande->date_acceptation,

                'date_fin' =>
                    $commande->date_fin,

                'montant_total' =>
                    $commande->montant_total,

                'delai_livraison_jours' =>
                    $commande->delai_livraison_jours,

                'nombre_revisions_incluses' =>
                    $commande->nombre_revisions_incluses,

                'livrables_convenus' =>
                    $commande->livrables_convenus,
            ],
        ], 202);
    }
}