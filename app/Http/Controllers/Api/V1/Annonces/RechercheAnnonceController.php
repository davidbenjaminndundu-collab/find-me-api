<?php

namespace App\Http\Controllers\Api\V1\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Annonces\RechercheAnnonceRequest;
use App\Models\Annonce;
use App\Services\Annonces\RechercheAnnonceService;
use Illuminate\Http\JsonResponse;

class RechercheAnnonceController extends Controller
{
    public function __construct(
        private readonly RechercheAnnonceService $service
    ) {
    }

    public function __invoke(RechercheAnnonceRequest $request): JsonResponse
    {
                $resultat = $this->service->rechercher(
                    $request->validated('q'),
                    $request->validated('id_categorie'),
                    $request->validated('limit'),
                    $request->validated('offset')
                );

        return response()->json([
            'data' => $resultat['data']->map(
                fn (Annonce $annonce) => $this->formaterAnnonce($annonce)
            )->values(),
            'meta' => $resultat['meta'],
        ]);
    }

    private function formaterAnnonce(Annonce $annonce): array
    {
        return [
            'id_annonce' => $annonce->id_annonce,
            'titre' => $annonce->titre,
            'description' => $annonce->description,
            'prix_base' => $annonce->prix_base,
            'delai_livraison_jours' => $annonce->delai_livraison_jours,
            'nombre_revisions' => $annonce->nombre_revisions,
            'livrables_inclus' => $annonce->livrables_inclus,
            'elements_requis_client' => $annonce->elements_requis_client,
            'image_couverture' => $annonce->image_couverture,
            'mots_cles' => $annonce->mots_cles,
            'conditions_particulieres' => $annonce->conditions_particulieres,
            'published_at' => $annonce->published_at,
            'categorie' => [
                'id_categorie' => $annonce->categorie->id_categorie,
                'nom' => $annonce->categorie->nom,
            ],
            'prestataire' => [
                'id_prestataire' => $annonce->prestataire->id_prestataire,
                'nom_professionnel' => $annonce->prestataire->nom_professionnel,
                'note_moyenne' => $annonce->prestataire->note_moyenne,
                'nombre_avis' => $annonce->prestataire->nombre_avis,
                'statut_disponibilite' => $annonce->prestataire->statut_disponibilite,
            ],
        ];
    }
}