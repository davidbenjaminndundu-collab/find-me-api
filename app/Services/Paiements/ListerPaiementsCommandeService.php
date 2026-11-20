<?php

namespace App\Services\Paiements;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ListerPaiementsCommandeService
{
    /**
     * @return array{data: Collection, meta: array{limit: int, offset: int, total: int}}
     */
    public function lister(Utilisateur $utilisateur, int $idCommande): array
    {
        if ($utilisateur->statut_compte !== StatutCompte::Actif) {
            throw new HttpException(403, 'Compte inactif.');
        }

        $commande = Commande::query()->find($idCommande);

        if ($commande === null) {
            throw new HttpException(404, 'Commande introuvable.');
        }

        $estAdmin = $utilisateur->role === RoleUtilisateur::Administrateur;
        $estClientProprietaire = $utilisateur->role === RoleUtilisateur::Client
            && (int) $commande->id_client === (int) $utilisateur->id_utilisateur;

        if (! $estAdmin && ! $estClientProprietaire) {
            throw new HttpException(
                403,
                'Action réservée au client de la commande ou à un administrateur.'
            );
        }

        $paiements = $commande->paiements()
            ->orderByDesc('initiated_at')
            ->orderByDesc('id_paiement')
            ->get();

        return [
            'data' => $paiements,
            'meta' => [
                'limit' => $paiements->count(),
                'offset' => 0,
                'total' => $paiements->count(),
            ],
        ];
    }
}