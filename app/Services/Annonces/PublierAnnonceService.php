<?php

namespace App\Services\Annonces;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublierAnnonceService
{
    public function publier(
        Utilisateur $utilisateur,
        int $idAnnonce
    ): Annonce {
        if (
            $utilisateur->role !== RoleUtilisateur::Prestataire
            || $utilisateur->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Seuls les prestataires actifs peuvent publier une annonce.'
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

        if ($annonce->statut !== StatutAnnonce::Brouillon) {
            throw new HttpException(
                409,
                'Seule une annonce en brouillon peut être publiée.'
            );
        }

        $annonce->update([
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
            'motif_refus' => null,
        ]);

        return $annonce->fresh();
    }
}