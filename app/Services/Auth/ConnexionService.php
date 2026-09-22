<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConnexionService
{
    public function connecter(array $donnees): Utilisateur
    {
        $utilisateur = Utilisateur::query()
            ->where('telephone', $donnees['telephone'])
            ->first();

        if (
            ! $utilisateur ||
            ! Hash::check(
                $donnees['mot_de_passe'],
                $utilisateur->mot_de_passe_hash
            )
        ) {
            throw new AuthenticationException(
                'Identifiants incorrects.'
            );
        }

        if ($utilisateur->statut_compte !== StatutCompte::Actif) {
            throw new HttpException(
                403,
                $this->messageStatutCompte(
                    $utilisateur->statut_compte
                )
            );
        }

        $utilisateur->update([
            'derniere_connexion_at' => now(),
        ]);

        return $utilisateur;
    }

    private function messageStatutCompte(StatutCompte $statut): string
    {
        return match ($statut) {
            StatutCompte::AVerifier =>
                'Votre numéro de téléphone doit encore être vérifié.',

            StatutCompte::Suspendu =>
                'Votre compte est temporairement suspendu.',

            StatutCompte::Bloque =>
                'Votre compte est bloqué.',

            StatutCompte::Ferme =>
                'Votre compte est fermé.',

            default =>
                'Votre compte ne peut pas accéder à la plateforme.',
        };
    }
}