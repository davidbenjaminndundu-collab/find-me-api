<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Administration\RefusAnnonceRequest;
use App\Models\Utilisateur;
use App\Services\Administration\ModerationAnnonceService;
use Illuminate\Http\JsonResponse;

class RefuserAnnonceController extends Controller
{
    public function __construct(
        private readonly ModerationAnnonceService $service
    ) {
    }

    public function __invoke(
        RefusAnnonceRequest $request,
        int $idAnnonce
    ): JsonResponse {
        /** @var Utilisateur $admin */
        $admin = $request->user();

        $annonce = $this->service->refuser(
            $admin,
            $idAnnonce,
            $request->validated('motif_refus')
        );

        return response()->json([
            'message' => 'Annonce refusée avec succès.',

            'annonce' => [
                'id_annonce' => $annonce->id_annonce,
                'titre' => $annonce->titre,
                'statut' => $annonce->statut,
                'motif_refus' => $annonce->motif_refus,
            ],
        ]);
    }
}