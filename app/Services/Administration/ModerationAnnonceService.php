<?php

namespace App\Services\Administration;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModerationAnnonceService
{
    public function valider(
        Utilisateur $admin,
        int $idAnnonce
    ): Annonce {
        $this->verifierAdministrateur($admin);

        $annonce = Annonce::query()
            ->find($idAnnonce);

        if ($annonce === null) {
            throw new HttpException(
                404,
                'Annonce introuvable.'
            );
        }

        if ($annonce->statut !== StatutAnnonce::Publiee) {
            throw new HttpException(
                409,
                'Seule une annonce publiée peut être validée.'
            );
        }

        if ($annonce->validee_admin_at !== null) {
            throw new HttpException(
                409,
                'Cette annonce a déjà été validée.'
            );
        }

        $annonce->update([
            'validee_admin_at' => now(),
            'motif_refus' => null,
        ]);

        return $annonce->fresh();
    }

    public function refuser(
        Utilisateur $admin,
        int $idAnnonce,
        string $motif
    ): Annonce {
        $this->verifierAdministrateur($admin);

        $annonce = Annonce::query()
            ->find($idAnnonce);

        if ($annonce === null) {
            throw new HttpException(
                404,
                'Annonce introuvable.'
            );
        }

        if ($annonce->statut !== StatutAnnonce::Publiee) {
            throw new HttpException(
                409,
                'Seule une annonce publiée peut être refusée.'
            );
        }

        $annonce->update([
            'statut' => StatutAnnonce::Refusee,
            'motif_refus' => $motif,
            'validee_admin_at' => null,
        ]);

        return $annonce->fresh();
    }

    private function verifierAdministrateur(
        Utilisateur $admin
    ): void {
        if (
            $admin->role !== RoleUtilisateur::Administrateur
            || $admin->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Action réservée aux administrateurs actifs.'
            );
        }
    }
}