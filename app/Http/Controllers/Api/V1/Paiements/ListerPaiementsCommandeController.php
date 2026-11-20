<?php

namespace App\Http\Controllers\Api\V1\Paiements;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Utilisateur;
use App\Services\Paiements\ListerPaiementsCommandeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListerPaiementsCommandeController extends Controller
{
    public function __construct(
        private readonly ListerPaiementsCommandeService $service
    ) {
    }

    public function __invoke(Request $request, int $idCommande): JsonResponse
    {
        /** @var Utilisateur $client */
        $client = $request->user();

        $resultat = $this->service->lister($client, $idCommande);

        return response()->json([
            'data' => $resultat['data']->map(
                fn (Paiement $paiement) => [
                    'id_paiement' => $paiement->id_paiement,
                    'id_commande' => $paiement->id_commande,
                    'reference_interne' => $paiement->reference_interne,
                    'montant' => $paiement->montant,
                    'devise' => $paiement->devise,
                    'operateur' => $paiement->operateur,
                    'numero_payeur_masque' => $paiement->numero_payeur_masque,
                    'statut' => $paiement->statut,
                ]
            )->values(),
            'meta' => $resultat['meta'],
        ]);
    }
}