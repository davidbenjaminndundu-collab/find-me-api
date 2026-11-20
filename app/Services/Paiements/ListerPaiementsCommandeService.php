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
    public function lister(Utilisateur $client, int $idCommande): array
    {
        $this->verifierClientActif($client);

        $commande = Commande::query()->find($idCommande);

        if ($commande === null) {
            throw new HttpException(404, 'Commande introuvable.');
        }

        if ((int) $commande->id_client !== (int) $client->id_utilisateur) {
            throw new HttpException(
                403,
                'Action réservée au client de la commande.'
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

    private function verifierClientActif(Utilisateur $client): void
    {
        if (
            $client->role !== RoleUtilisateur::Client
            || $client->statut_compte !== StatutCompte::Actif
        ) {
            throw new HttpException(
                403,
                'Action réservée aux clients actifs.'
            );
        }
    }
}