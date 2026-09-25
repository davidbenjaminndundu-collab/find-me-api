<?php

namespace App\Services\Profile;

use App\Models\Utilisateur;

class ModificationProfilService
{
    public function modifier(
        Utilisateur $utilisateur,
        array $donnees
    ): Utilisateur {
        $utilisateur->update($donnees);

        return $utilisateur->refresh();
    }
}