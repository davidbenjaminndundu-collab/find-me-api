<?php

namespace App\Services\Annonces;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Models\Annonce;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModifierAnnonceService
{
    public function modifier(Utilisateur $utilisateur, int $idAnnonce, array $donnees): Annonce
    {
        if (
            $utilisateur->role !== RoleUtilisateur::Prestataire
            || $utilisateur->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(403, 'Seuls les prestataires actifs peuvent modifier une annonce.');
        }

        $profil = ProfilPrestataire::query()
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->first();

        if ($profil === null) {
            throw new HttpException(403, 'Profil prestataire requis.');
        }

        $annonce = Annonce::query()->find($idAnnonce);

        if ($annonce === null) {
            throw new HttpException(404, 'Annonce introuvable.');
        }

        if ($annonce->id_prestataire !== $profil->id_prestataire) {
            throw new HttpException(403, 'Vous ne pouvez modifier que vos propres annonces.');
        }

        if (! in_array($annonce->statut, [
            StatutAnnonce::Brouillon,
            StatutAnnonce::Refusee,
        ], true)) {
            throw new HttpException(409, 'Statut non modifiable.');
        }

        $annonce->update($donnees);

        return $annonce->fresh();
    }
}