<?php

namespace App\Services\Categories;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Categorie;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreerCategorieService
{
    public function creer(Utilisateur $admin, array $donnees): Categorie
    {
        $this->verifierAdministrateur($admin);

        return Categorie::query()->create([
            'nom' => $donnees['nom'],
            'description' => $donnees['description'] ?? null,
            'icone' => $donnees['icone'] ?? null,
            'est_active' => $donnees['est_active'] ?? true,
        ]);
    }

    private function verifierAdministrateur(Utilisateur $admin): void
    {
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