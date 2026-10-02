<?php

namespace App\Services\Categories;

use App\Models\Categorie;
use Illuminate\Support\Collection;

class ListerCategoriesService
{
    /**
     * @return array{data: Collection<int, Categorie>, meta: array{limit: int, offset: int, total: int}}
     */
    public function lister(
        ?bool $estActive,
        ?int $limit,
        ?int $offset
    ): array {
        $limit = $limit ?? 20;
        $offset = $offset ?? 0;

        $query = Categorie::query();

        if ($estActive !== null) {
            $query->where('est_active', $estActive);
        }

        $total = (clone $query)->count();

        $categories = $query
            ->orderBy('nom')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'data' => $categories,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ];
    }
}