<?php

namespace App\Http\Controllers\Api\V1\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Categories\CreerCategorieRequest;
use App\Models\Utilisateur;
use App\Services\Categories\CreerCategorieService;
use Illuminate\Http\JsonResponse;

class CreerCategorieController extends Controller
{
    public function __construct(
        private readonly CreerCategorieService $service
    ) {
    }

    public function __invoke(CreerCategorieRequest $request): JsonResponse
    {
        /** @var Utilisateur $admin */
        $admin = $request->user();

        $categorie = $this->service->creer(
            $admin,
            $request->validated()
        );

        return response()->json([
            'message' => 'Catégorie créée.',
            'categorie' => [
                'id_categorie' => $categorie->id_categorie,
                'nom' => $categorie->nom,
                'description' => $categorie->description,
                'icone' => $categorie->icone,
                'est_active' => $categorie->est_active,
            ],
        ], 201);
    }
}