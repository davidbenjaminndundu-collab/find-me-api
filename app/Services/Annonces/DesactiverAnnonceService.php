<?php

namespace App\Services\Annonces;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DesactiverAnnonceService
{
    public function desactiver(
        Utilisateur $utilisateur,
        int $idAnnonce
    ): Annonce {
        if (
            $utilisateur->role !== RoleUtilisateur::Prestataire
            || $utilisateur->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Seuls les prestataires actifs peuvent désactiver une annonce.'
            );
        }

        $profil = $utilisateur->profilPrestataire;

        if ($profil === null) {
            throw new HttpException(
                403,
                'Profil prestataire requis.'
            );
        }

        $annonce = Annonce::query()
            ->where('id_annonce', $idAnnonce)
            ->where('id_prestataire', $profil->id_prestataire)
            ->first();

        if ($annonce === null) {
            throw new HttpException(
                404,
                'Annonce introuvable.'
            );
        }

        if ($annonce->statut !== StatutAnnonce::Publiee) {
            throw new HttpException(
                409,
                'Seule une annonce publiée peut être désactivée.'
            );
        }

        $annonce->update([
            'statut' => StatutAnnonce::Desactivee,
        ]);

        return $annonce->fresh();
    }
}