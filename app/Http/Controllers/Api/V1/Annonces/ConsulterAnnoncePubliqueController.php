<?php

namespace App\Http\Controllers\Api\V1\Annonces;

use App\Http\Controllers\Controller;
use App\Services\Annonces\ConsulterAnnoncePubliqueService;
use Illuminate\Http\JsonResponse;

class ConsulterAnnoncePubliqueController extends Controller
{
    public function __construct(
        private readonly ConsulterAnnoncePubliqueService $service
    ) {
    }

    public function __invoke(int $idAnnonce): JsonResponse
    {
        $annonce = $this->service->consulter($idAnnonce);

        return response()->json([
            'annonce' => [
                'id_annonce' =>
                    $annonce->id_annonce,

                'titre' =>
                    $annonce->titre,

                'description' =>
                    $annonce->description,

                'prix_base' =>
                    $annonce->prix_base,

                'delai_livraison_jours' =>
                    $annonce->delai_livraison_jours,

                'nombre_revisions' =>
                    $annonce->nombre_revisions,

                'livrables_inclus' =>
                    $annonce->livrables_inclus,

                'elements_requis_client' =>
                    $annonce->elements_requis_client,

                'image_couverture' =>
                    $annonce->image_couverture,

                'mots_cles' =>
                    $annonce->mots_cles,

                'conditions_particulieres' =>
                    $annonce->conditions_particulieres,

                'published_at' =>
                    $annonce->published_at,

                'categorie' => [
                    'id_categorie' =>
                        $annonce->categorie->id_categorie,

                    'nom' =>
                        $annonce->categorie->nom,
                ],

                'prestataire' => [
                    'id_prestataire' =>
                        $annonce->prestataire->id_prestataire,

                    'nom_professionnel' =>
                        $annonce->prestataire->nom_professionnel,

                    'note_moyenne' =>
                        $annonce->prestataire->note_moyenne,

                    'nombre_avis' =>
                        $annonce->prestataire->nombre_avis,

                    'statut_disponibilite' =>
                        $annonce->prestataire->statut_disponibilite,
                ],
            ],
        ]);
    }
}