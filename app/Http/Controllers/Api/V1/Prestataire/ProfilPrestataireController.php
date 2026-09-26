<?php

namespace App\Http\Controllers\Api\V1\Prestataire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prestataire\ProfilPrestataireRequest;
use App\Models\Utilisateur;
use App\Services\Prestataire\ProfilPrestataireService;
use Illuminate\Http\JsonResponse;

class ProfilPrestataireController extends Controller
{
    public function __construct(
        private readonly ProfilPrestataireService $service
    ) {
    }

    public function __invoke(
        ProfilPrestataireRequest $request
    ): JsonResponse {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $profil = $this->service->completer(
            $utilisateur,
            $request->validated()
        );

        return response()->json([
            'message' =>
                'Profil professionnel enregistré avec succès.',

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
            ],
        ]);
    }
}