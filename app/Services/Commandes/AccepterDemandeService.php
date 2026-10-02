<?php

namespace App\Services\Commandes;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccepterDemandeService
{
    public function accepter(
        Utilisateur $utilisateur,
        int $idCommande
    ): Commande {
        $this->verifierPrestataireActif($utilisateur);

        $profil = $utilisateur->profilPrestataire;

        if ($profil === null) {
            throw new HttpException(
                403,
                'Profil prestataire requis.'
            );
        }

        return DB::transaction(function () use (
            $profil,
            $idCommande
        ): Commande {
            $commande = Commande::query()
                ->where('id_commande', $idCommande)
                ->where(
                    'id_prestataire',
                    $profil->id_prestataire
                )
                ->lockForUpdate()
                ->first();

            if ($commande === null) {
                throw new HttpException(
                    404,
                    'Demande introuvable.'
                );
            }

            if ($commande->est_offre_personnalisee) {
                throw new HttpException(
                    409,
                    'Cette action concerne uniquement une demande standard.'
                );
            }

            if (
                $commande->statut
                !== StatutCommande::EnAttenteReponse
            ) {
                throw new HttpException(
                    409,
                    'Cette demande ne peut pas être acceptée dans son état actuel.'
                );
            }

            $commande->update([
                'statut' =>
                    StatutCommande::EnAttentePaiement,

                'date_acceptation' =>
                    now(),
            ]);

            return $commande->fresh();
        });
    }

    private function verifierPrestataireActif(
        Utilisateur $utilisateur
    ): void {
        if (
            $utilisateur->role
                !== RoleUtilisateur::Prestataire
            || $utilisateur->statut_compte
                !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Action réservée aux prestataires actifs.'
            );
        }
    }
}