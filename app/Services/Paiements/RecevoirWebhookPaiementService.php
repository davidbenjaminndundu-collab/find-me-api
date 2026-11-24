<?php

namespace App\Services\Paiements;

use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Models\CallbackPaiement;
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

        if ($payload['event'] !== 'payment.completed') {
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
            throw new HttpException(503, 'Paiement introuvable, réessayez plus tard.');
        }

        $identifiant = $sessionId.':payment.completed';

        $dejaVu = CallbackPaiement::query()
            ->where('operateur', 'nyole')
            ->where('identifiant_evenement', $identifiant)
            ->exists();

        if ($dejaVu) {
            return ['message' => 'Événement déjà traité.'];
        }

        return DB::transaction(function () use ($paiement, $payload, $identifiant, $corpsBrut): array {
            $commande = $paiement->commande()->lockForUpdate()->first();
            $paiement = Paiement::query()
                ->where('id_paiement', $paiement->id_paiement)
                ->lockForUpdate()
                ->first();

            $montantAttendu = (int) bcmul((string) $paiement->montant, '100', 0);
            $montantRecu = $payload['data']['amount'] ?? null;
            $deviseRecue = $payload['data']['currency'] ?? null;
            $coherent = $commande !== null
                && $commande->statut === StatutCommande::PaiementEnCours
                && $paiement->statut === StatutPaiement::EnAttente
                && $deviseRecue === 'USD'
                && (int) $montantRecu === $montantAttendu;

            if ($coherent) {
                $paiement->update([
                    'statut' => StatutPaiement::Confirme,
                    'confirmed_at' => now(),
                ]);

                $commande->update([
                    'statut' => StatutCommande::Payee,
                    'date_paiement' => now(),
                ]);
            }

            try {
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
            } catch (QueryException) {
                return ['message' => 'Événement déjà traité.'];
            }

            return [
                'message' => $coherent
                    ? 'Paiement confirmé.'
                    : 'Événement reçu sans changement de statut.',
            ];
        });
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