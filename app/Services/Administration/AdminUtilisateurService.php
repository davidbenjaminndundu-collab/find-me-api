<?php

namespace App\Services\Administration;

use App\Enums\RoleUtilisateur;
use App\Models\Utilisateur;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminUtilisateurService
{
    public function modifierStatut(Utilisateur $admin, int $idCible, array $donnees): Utilisateur
    {
        if ($admin->role !== RoleUtilisateur::Administrateur) {
            throw new HttpException(403, 'Action reservee a l\'administrateur.');
        }

        if ($admin->id_utilisateur === $idCible) {
            throw new HttpException(403, 'Un administrateur ne peut pas modifier son propre statut.');
        }

        $cible = Utilisateur::query()->find($idCible);

        if ($cible === null) {
            throw new HttpException(404, 'Utilisateur introuvable.');
        }

        $cible->update([
            'statut_compte' => $donnees['statut_compte'],
        ]);

        return $cible->fresh();
    }
}