<?php

namespace App\Services\Paiements;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Enums\StatutPaiement;
use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InitierPaiementService
{
    public function initier(Utilisateur $client, int $idCommande, array $donnees): array
    {
        $this->verifierClientActif($client);

        $existant = Paiement::query()
            ->where('idempotency_key', $donnees['cle_idempotence'])
            ->first();

        if ($existant !== null) {
            if ((int) $existant->id_commande !== $idCommande) {
                throw new HttpException(
                    409,
                    'Cette clé d’idempotence a déjà été utilisée pour un autre paiement.'
                );
            }

            return $this->presenter($existant);
        }

        return DB::transaction(function () use ($client, $idCommande, $donnees): array {
            $commande = Commande::query()
                ->where('id_commande', $idCommande)
                ->lockForUpdate()
                ->first();

            if ($commande === null) {
                throw new HttpException(404, 'Commande introuvable.');
            }

            if ((int) $commande->id_client !== (int) $client->id_utilisateur) {
                throw new HttpException(
                    403,
                    'Action réservée au client de la commande.'
                );
            }

            if ($commande->statut !== StatutCommande::EnAttentePaiement) {
                throw new HttpException(
                    409,
                    'Cette commande ne peut pas être payée dans son état actuel.'
                );
            }

            $session = $this->creerSessionNyole($client, $commande, $donnees);

            $paiement = Paiement::query()->create([
                'id_commande' => $commande->id_commande,
                'reference_interne' => $this->referenceInterne(),
                'montant' => $commande->montant_total,
                'devise' => 'USD',
                'operateur' => $donnees['operateur'],
                'numero_payeur_masque' => $this->masquerNumero($donnees['numero_payeur']),
                'reference_operateur' => $session['id'],
                'statut' => StatutPaiement::EnAttente,
                'idempotency_key' => $donnees['cle_idempotence'],
                'initiated_at' => now(),
            ]);

            $commande->update([
                'statut' => StatutCommande::PaiementEnCours,
            ]);

            return $this->presenter($paiement, $session['url']);
        });
    }

    private function creerSessionNyole(Utilisateur $client, Commande $commande, array $donnees): array
    {
        $secret = config('services.nyole.secret');

        if (! is_string($secret) || $secret === '') {
            throw new HttpException(502, 'Le service de paiement est indisponible.');
        }

        $montantCents = (int) bcmul((string) $commande->montant_total, '100', 0);

        $reponse = Http::baseUrl(config('services.nyole.base_url'))
            ->withToken($secret)
            ->acceptJson()
            ->timeout(15)
            ->withHeaders([
                'Idempotency-Key' => $donnees['cle_idempotence'],
            ])
            ->post('/v1/checkout/sessions', [
                'amount' => $montantCents,
                'currency' => 'USD',
                'customer_name' => trim($client->prenom.' '.$client->nom),
                'customer_phone' => $donnees['numero_payeur'],
                'description' => 'Commande '.$commande->reference_commande,
                'success_url' => config('services.nyole.success_url'),
                'cancel_url' => config('services.nyole.cancel_url'),
                'metadata' => [
                    'id_commande' => (string) $commande->id_commande,
                    'reference_commande' => $commande->reference_commande,
                    'operateur' => $donnees['operateur'],
                ],
            ]);

        $session = $reponse->json();

        if (
            ! $reponse->successful()
            || ! is_array($session)
            || empty($session['id'])
            || empty($session['url'])
        ) {
            throw new HttpException(502, 'Le service de paiement est indisponible.');
        }

        return [
            'id' => (string) $session['id'],
            'url' => (string) $session['url'],
        ];
    }

    private function presenter(Paiement $paiement, ?string $url = null): array
    {
        return [
            'paiement' => $paiement,
            'url_paiement' => $url ?? 'https://app.nyole.com/checkout/'.$paiement->reference_operateur,
        ];
    }

    private function referenceInterne(): string
    {
        return 'PAY-'.strtoupper((string) Str::ulid());
    }

    private function masquerNumero(string $numero): string
    {
        return substr($numero, 0, 4).'*****'.substr($numero, -4);
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