<?php

namespace App\Http\Controllers\Api\V1\Prestataire;

use App\Http\Controllers\Controller;
use App\Services\Prestataire\ConsultationProfilPrestataireService;
use Illuminate\Http\JsonResponse;

class ConsultationProfilPrestataireController extends Controller
{
    public function __construct(
        private readonly ConsultationProfilPrestataireService $service
    ) {
    }

    public function __invoke(int $idPrestataire): JsonResponse
    {
        $profil = $this->service->consulter($idPrestataire);

        return response()->json([
            'profil' => [
                'id_prestataire' =>
                    $profil->id_prestataire,

                'nom_professionnel' =>
                    $profil->nom_professionnel,

                'bio' =>
                    $profil->bio,

                'competences' =>
                    $profil->competences,

                'portfolio_url' =>
                    $profil->portfolio_url,

                'note_moyenne' =>
                    $profil->note_moyenne,

                'nombre_avis' =>
                    $profil->nombre_avis,

                'statut_disponibilite' =>
                    $profil->statut_disponibilite,

                'utilisateur' => [
                    'nom' =>
                        $profil->utilisateur->nom,

                    'prenom' =>
                        $profil->utilisateur->prenom,

                    'image_profil' =>
                        $profil->utilisateur->image_profil,
                ],
            ],
        ]);
    }
}