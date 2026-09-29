<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\Administration\ModerationAnnonceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValiderAnnonceController extends Controller
{
    public function __construct(
        private readonly ModerationAnnonceService $service
    ) {
    }

    public function __invoke(
        Request $request,
        int $idAnnonce
    ): JsonResponse {
        /** @var Utilisateur $admin */
        $admin = $request->user();

        $annonce = $this->service->valider(
            $admin,
            $idAnnonce
        );

        return response()->json([
            'message' => 'Annonce validée avec succès.',

            'annonce' => [
                'id_annonce' => $annonce->id_annonce,
                'titre' => $annonce->titre,
                'statut' => $annonce->statut,
                'validee_admin_at' =>
                    $annonce->validee_admin_at,
            ],
        ]);
    }
}