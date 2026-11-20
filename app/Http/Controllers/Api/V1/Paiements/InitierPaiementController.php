<?php

namespace App\Http\Controllers\Api\V1\Paiements;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Paiements\InitierPaiementRequest;
use App\Models\Utilisateur;
use App\Services\Paiements\InitierPaiementService;
use Illuminate\Http\JsonResponse;

class InitierPaiementController extends Controller
{
    public function __construct(
        private readonly InitierPaiementService $service
    ) {
    }

    public function __invoke(InitierPaiementRequest $request, int $idCommande): JsonResponse
    {
        /** @var Utilisateur $client */
        $client = $request->user();

        $resultat = $this->service->initier(
            $client,
            $idCommande,
            $request->validated()
        );

        $paiement = $resultat['paiement'];

        return response()->json([
            'message' => 'Paiement initié.',
            'url_paiement' => $resultat['url_paiement'],
            'paiement' => [
                'id_paiement' => $paiement->id_paiement,
                'id_commande' => $paiement->id_commande,
                'reference_interne' => $paiement->reference_interne,
                'montant' => $paiement->montant,
                'devise' => $paiement->devise,
                'operateur' => $paiement->operateur,
                'numero_payeur_masque' => $paiement->numero_payeur_masque,
                'statut' => $paiement->statut,
            ],
        ], 202);
    }
}