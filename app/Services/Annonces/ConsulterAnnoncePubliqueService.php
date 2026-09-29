<?php

namespace App\Services\Annonces;

use App\Enums\StatutAnnonce;
use App\Models\Annonce;

class ConsulterAnnoncePubliqueService
{
    public function consulter(int $idAnnonce): Annonce
    {
        return Annonce::query()
            ->with([
                'categorie',
                'prestataire.utilisateur',
            ])
            ->where('id_annonce', $idAnnonce)
            ->where('statut', StatutAnnonce::Publiee)
            ->firstOrFail();
    }
}