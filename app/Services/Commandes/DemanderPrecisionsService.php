<?php

namespace App\Services\Commandes;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Models\Commande;
use App\Models\PrecisionCommande;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DemanderPrecisionsService
{
    public function demander(
        Utilisateur $utilisateur,
        int $idCommande,
        string $motif
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
            $idCommande,
            $motif
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

            if (
                $commande->statut
                !== StatutCommande::EnAttenteReponse
            ) {
                throw new HttpException(
                    409,
                    'Des précisions ne peuvent pas être demandées dans cet état.'
                );
            }

            PrecisionCommande::query()->create([
                'id_commande' =>
                    $commande->id_commande,

                'demande' =>
                    trim($motif),

                'demande_at' =>
                    now(),
            ]);

            $commande->update([
                'statut' =>
                    StatutCommande::InformationsDemandees,
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