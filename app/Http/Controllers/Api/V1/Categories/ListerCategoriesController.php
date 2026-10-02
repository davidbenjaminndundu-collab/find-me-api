<?php

namespace App\Http\Controllers\Api\V1\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Categories\ListerCategoriesRequest;
use App\Models\Categorie;
use App\Services\Categories\ListerCategoriesService;
use Illuminate\Http\JsonResponse;

class ListerCategoriesController extends Controller
{
    public function __construct(
        private readonly ListerCategoriesService $service
    ) {
    }

    public function __invoke(ListerCategoriesRequest $request): JsonResponse
    {
        $estActive = $request->validated('est_active');

        if ($estActive !== null) {
            $estActive = filter_var($estActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $resultat = $this->service->lister(
            $estActive,
            $request->validated('limit'),
            $request->validated('offset')
        );

        return response()->json([
            'data' => $resultat['data']->map(
                fn (Categorie $categorie) => [
                    'id_categorie' => $categorie->id_categorie,
                    'nom' => $categorie->nom,
                    'description' => $categorie->description,
                    'icone' => $categorie->icone,
                    'est_active' => $categorie->est_active,
                ]
            )->values(),
            'meta' => $resultat['meta'],
        ]);
    }
}