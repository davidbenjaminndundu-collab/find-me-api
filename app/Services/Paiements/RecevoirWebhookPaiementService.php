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

        $evenement = $this->normaliserDepuisNyole($payload);

        if ($evenement['event'] !== 'payment.completed') {
            return ['message' => 'Événement ignoré.'];
        }

        if ($evenement['reference_session'] === null || $evenement['identifiant_evenement'] === null) {
            throw new HttpException(400, 'Payload du webhook inexploitable.');
        }

        $paiement = Paiement::query()
            ->where('reference_operateur', $evenement['reference_session'])
            ->first();

        if ($paiement === null) {
            throw new HttpException(503, 'Paiement introuvable, réessayez plus tard.');
        }

        try {
            return DB::transaction(function () use ($paiement, $evenement, $corpsBrut): array {
                $commande = $paiement->commande()->lockForUpdate()->first();
                $paiement = Paiement::query()
                    ->where('id_paiement', $paiement->id_paiement)
                    ->lockForUpdate()
                    ->first();

                $dejaVu = CallbackPaiement::query()
                    ->where('operateur', 'nyole')
                    ->where('identifiant_evenement', $evenement['identifiant_evenement'])
                    ->exists();

                if ($dejaVu) {
                    return ['message' => 'Événement déjà traité.'];
                }

                $montantAttendu = (int) bcmul((string) $paiement->montant, '100', 0);
                $montantRecu = $evenement['montant_centimes'];
                $montantEntierPositif = is_int($montantRecu) && $montantRecu > 0;
                $coherent = $commande !== null
                    && $commande->statut === StatutCommande::PaiementEnCours
                    && $paiement->statut === StatutPaiement::EnAttente
                    && $evenement['devise'] === 'USD'
                    && $montantEntierPositif
                    && $montantRecu === $montantAttendu
                    && $evenement['statut_metier'] === 'confirme';

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

                CallbackPaiement::query()->create([
                    'id_paiement' => $paiement->id_paiement,
                    'operateur' => 'nyole',
                    'identifiant_evenement' => $evenement['identifiant_evenement'],
                    'signature_valide' => true,
                    'statut_recu' => $evenement['statut_nyole'],
                    'montant_recu' => $paiement->montant,
                    'reference_operateur' => $evenement['reference_provider'],
                    'payload' => json_decode($corpsBrut, true),
                    'resultat_traitement' => $coherent ? 'traite' : 'incoherent',
                    'processed_at' => now(),
                ]);

                return [
                    'message' => $coherent
                        ? 'Paiement confirmé.'
                        : 'Événement reçu sans changement de statut.',
                ];
            });
        } catch (QueryException $exception) {
            if ($this->estDoublon($exception)) {
                return ['message' => 'Événement déjà traité.'];
            }

            throw $exception;
        }
    }

    /**
     * Adaptateur interne : payload natif Nyole → forme métier.
     *
     * @return array{
     *     event: string,
     *     operateur: string,
     *     reference_session: ?string,
     *     identifiant_evenement: ?string,
     *     reference_provider: ?string,
     *     montant_centimes: mixed,
     *     devise: mixed,
     *     statut_nyole: mixed,
     *     statut_metier: ?string
     * }
     */
    private function normaliserDepuisNyole(array $payload): array
    {
        $event = $payload['event'];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $sessionId = is_string($data['id'] ?? null) && $data['id'] !== ''
            ? $data['id']
            : null;
        $statutNyole = $data['status'] ?? null;

        return [
            'event' => $event,
            'operateur' => 'nyole',
            'reference_session' => $sessionId,
            'identifiant_evenement' => $sessionId !== null ? $sessionId.':'.$event : null,
            'reference_provider' => is_string($data['provider_reference'] ?? null)
                ? $data['provider_reference']
                : null,
            'montant_centimes' => $data['amount'] ?? null,
            'devise' => $data['currency'] ?? null,
            'statut_nyole' => $statutNyole,
            'statut_metier' => ($event === 'payment.completed' && $statutNyole === 'SUCCESS')
                ? 'confirme'
                : null,
        ];
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