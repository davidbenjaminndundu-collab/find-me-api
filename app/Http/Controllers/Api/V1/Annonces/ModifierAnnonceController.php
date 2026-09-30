<?php

namespace App\Http\Controllers\Api\V1\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Annonces\ModifierAnnonceRequest;
use App\Models\Utilisateur;
use App\Services\Annonces\ModifierAnnonceService;
use Illuminate\Http\JsonResponse;

class ModifierAnnonceController extends Controller
{
    public function __construct(
        private readonly ModifierAnnonceService $service
    ) {}

    public function __invoke(ModifierAnnonceRequest $request, int $id): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $annonce = $this->service->modifier(
            $utilisateur,
            $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Annonce mise a jour.',
            'annonce' => [
                'id_annonce' => $annonce->id_annonce,
                'id_categorie' => $annonce->id_categorie,
                'id_prestataire' => $annonce->id_prestataire,
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
                'statut' => $annonce->statut,
            ],
        ]);
    }
}