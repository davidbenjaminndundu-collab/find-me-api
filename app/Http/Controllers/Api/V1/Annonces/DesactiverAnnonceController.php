<?php

namespace App\Http\Controllers\Api\V1\Annonces;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\Annonces\DesactiverAnnonceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesactiverAnnonceController extends Controller
{
    public function __construct(
        private readonly DesactiverAnnonceService $service
    ) {
    }

    public function __invoke(
        Request $request,
        int $idAnnonce
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $annonce = $this->service->desactiver(
            $utilisateur,
            $idAnnonce
        );

        return response()->json([
            'message' => 'Annonce désactivée avec succès.',

            'annonce' => [
                'id_annonce' => $annonce->id_annonce,
                'titre' => $annonce->titre,
                'statut' => $annonce->statut,
                'published_at' => $annonce->published_at,
            ],
        ]);
    }
}