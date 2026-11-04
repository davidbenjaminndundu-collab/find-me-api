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

class RepondrePrecisionsService
{
    public function repondre(
        Utilisateur $utilisateur,
        int $idCommande,
        string $description
    ): Commande {
        $this->verifierClientActif($utilisateur);

        return DB::transaction(function () use (
            $utilisateur,
            $idCommande,
            $description
        ): Commande {
            $commande = Commande::query()
                ->where('id_commande', $idCommande)
                ->where(
                    'id_client',
                    $utilisateur->id_utilisateur
                )
                ->lockForUpdate()
                ->first();

            if ($commande === null) {
                throw new HttpException(
                    404,
                    'Commande introuvable.'
                );
            }

            if (
                $commande->statut
                !== StatutCommande::InformationsDemandees
            ) {
                throw new HttpException(
                    409,
                    'Cette commande n’attend pas de précisions.'
                );
            }

            $precision = PrecisionCommande::query()
                ->where(
                    'id_commande',
                    $commande->id_commande
                )
                ->whereNull('reponse_at')
                ->latest('id_precision')
                ->lockForUpdate()
                ->first();

            if ($precision === null) {
                throw new HttpException(
                    409,
                    'Aucune demande de précisions en attente.'
                );
            }

            $precision->update([
                'reponse' =>
                    trim($description),

                'reponse_at' =>
                    now(),
            ]);

            $commande->update([
                'statut' =>
                    StatutCommande::EnAttenteReponse,
            ]);

            return $commande->fresh();
        });
    }

    private function verifierClientActif(
        Utilisateur $utilisateur
    ): void {
        if (
            $utilisateur->role
                !== RoleUtilisateur::Client
            || $utilisateur->statut_compte
                !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Action réservée aux clients actifs.'
            );
        }
    }
}