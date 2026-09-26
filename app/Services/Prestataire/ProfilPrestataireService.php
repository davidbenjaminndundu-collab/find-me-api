<?php

namespace App\Services\Prestataire;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProfilPrestataireService
{
    public function completer(
        Utilisateur $utilisateur,
        array $donnees
    ): ProfilPrestataire {
        if ($utilisateur->role !== RoleUtilisateur::Prestataire) {
            throw new AccessDeniedHttpException(
                'Cette fonctionnalité est réservée aux prestataires.'
            );
        }

        if ($utilisateur->statut_compte !== StatutCompte::Actif) {
            throw new AccessDeniedHttpException(
                'Le compte doit être actif.'
            );
        }

        return ProfilPrestataire::query()->updateOrCreate(
            [
                'id_utilisateur' =>
                    $utilisateur->id_utilisateur,
            ],
            [
                'nom_professionnel' =>
                    $donnees['nom_professionnel'],

                'bio' =>
                    $donnees['bio'],

                'competences' =>
                    $donnees['competences'],

                'portfolio_url' =>
                    $donnees['portfolio_url'] ?? null,

                'statut_disponibilite' =>
                    $donnees['statut_disponibilite'],
            ]
        );
    }
}