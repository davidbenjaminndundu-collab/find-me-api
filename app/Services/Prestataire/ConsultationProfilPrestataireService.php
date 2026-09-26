<?php

namespace App\Services\Prestataire;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\ProfilPrestataire;

class ConsultationProfilPrestataireService
{
    public function consulter(int $idPrestataire): ProfilPrestataire
    {
        return ProfilPrestataire::query()
            ->with('utilisateur')
            ->where('id_prestataire', $idPrestataire)
            ->whereHas('utilisateur', function ($query) {
                $query
                    ->where(
                        'role',
                        RoleUtilisateur::Prestataire->value
                    )
                    ->where(
                        'statut_compte',
                        StatutCompte::Actif->value
                    );
            })
            ->firstOrFail();
    }
}