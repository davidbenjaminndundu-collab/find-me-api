<?php

namespace App\Services\Paiements;

use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Models\CallbackPaiement;
use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecevoirWebhookPaiementService
{
    private const ECART_MAX_SECONDES = 300;

    public function recevoir(string $corpsBrut, ?string $horodatage, ?string $signature): array
    {
        if (! $this->signatureValide($corpsBrut, $horodatage, $signature)) {
            throw new HttpException(401, 'Signature du webhook invalide.');
        }

        $payload = json_decode($corpsBrut, true);

        if (! is_array($payload) || ! is_string($payload['event'] ?? null)) {
            throw new HttpException(400, 'Payload du webhook inexploitable.');
        }

        $event = $payload['event'];

        if (! in_array($event, ['payment.completed', 'payment.failed', 'payment.cancelled'], true)) {
            return ['message' => 'Événement ignoré.'];
        }

        $sessionId = $payload['data']['id'] ?? null;

        if (! is_string($sessionId) || $sessionId === '') {
            throw new HttpException(400, 'Payload du webhook inexploitable.');
        }

        $paiement = Paiement::query()
            ->where('reference_operateur', $sessionId)
            ->first();

        if ($paiement === null) {
            return ['message' => 'Événement ignoré.'];
        }

        $identifiant = $sessionId.':'.$event;

        try {
            return DB::transaction(function () use ($paiement, $payload, $identifiant, $corpsBrut, $event): array {
                $commande = $paiement->commande()->lockForUpdate()->first();
                $paiement = Paiement::query()
                    ->where('id_paiement', $paiement->id_paiement)
                    ->lockForUpdate()
                    ->first();

                $dejaVu = CallbackPaiement::query()
                    ->where('operateur', 'nyole')
                    ->where('identifiant_evenement', $identifiant)
                    ->exists();

                if ($dejaVu) {
                    return ['message' => 'Événement déjà traité.'];
                }

                $coherent = $this->estCoherent($paiement, $commande, $payload);

                if ($coherent) {
                    $this->appliquer($paiement, $commande, $event);
                }

                CallbackPaiement::query()->create([
                    'id_paiement' => $paiement->id_paiement,
                    'operateur' => 'nyole',
                    'identifiant_evenement' => $identifiant,
                    'signature_valide' => true,
                    'statut_recu' => $payload['data']['status'] ?? null,
                    'montant_recu' => $paiement->montant,
                    'reference_operateur' => $payload['data']['provider_reference'] ?? null,
                    'payload' => json_decode($corpsBrut, true),
                    'resultat_traitement' => $coherent ? 'traite' : 'incoherent',
                    'processed_at' => now(),
                ]);

                if (! $coherent) {
                    return ['message' => 'Événement reçu sans changement de statut.'];
                }

                return [
                    'message' => match ($event) {
                        'payment.completed' => 'Paiement confirmé.',
                        'payment.failed' => 'Paiement échoué.',
                        'payment.cancelled' => 'Paiement expiré.',
                    },
                ];
            });
        } catch (QueryException $exception) {
            if ($this->estDoublon($exception)) {
                return ['message' => 'Événement déjà traité.'];
            }

            throw $exception;
        }
    }

    private function estCoherent(Paiement $paiement, ?Commande $commande, array $payload): bool
    {
        $montantAttendu = (int) bcmul((string) $paiement->montant, '100', 0);

        return $commande !== null
            && $commande->statut === StatutCommande::PaiementEnCours
            && $paiement->statut === StatutPaiement::EnAttente
            && ($payload['data']['currency'] ?? null) === 'USD'
            && (int) ($payload['data']['amount'] ?? null) === $montantAttendu;
    }

    private function appliquer(Paiement $paiement, Commande $commande, string $event): void
    {
        if ($event === 'payment.completed') {
            $paiement->update([
                'statut' => StatutPaiement::Confirme,
                'confirmed_at' => now(),
            ]);

            $commande->update([
                'statut' => StatutCommande::Payee,
                'date_paiement' => now(),
            ]);

            return;
        }

        $paiement->update([
            'statut' => $event === 'payment.failed'
                ? StatutPaiement::Echoue
                : StatutPaiement::Expire,
            'failed_at' => now(),
        ]);

        $commande->update([
            'statut' => StatutCommande::EnAttentePaiement,
        ]);
    }

    private function estDoublon(QueryException $exception): bool
    {
        return $exception->getCode() === '23505';
    }

    private function signatureValide(string $corpsBrut, ?string $horodatage, ?string $signature): bool
    {
        $secret = config('services.nyole.secret');

        if (
            ! is_string($secret)
            || $secret === ''
            || ! is_string($horodatage)
            || ! ctype_digit($horodatage)
            || ! is_string($signature)
        ) {
            return false;
        }

        if (abs(time() - (int) $horodatage) > self::ECART_MAX_SECONDES) {
            return false;
        }

        $valeur = null;

        foreach (explode(',', $signature) as $partie) {
            if (str_starts_with($partie, 'v1=')) {
                $valeur = substr($partie, 3);
            }
        }

        if ($valeur === null || $valeur === '') {
            return false;
        }

        $attendu = hash_hmac('sha256', $horodatage.'.'.$corpsBrut, $secret);

        return hash_equals($attendu, $valeur);
    }
}