<?php

namespace App\Http\Controllers\Api\V1\Paiements;

use App\Http\Controllers\Controller;
use App\Services\Paiements\RecevoirWebhookPaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook Nyole (Afriflow) — appel direct, sans adaptateur HTTP.
 *
 * En-têtes attendus :
 * - X-Afriflow-Timestamp : horodatage Unix (secondes)
 * - X-Afriflow-Signature : v1=<hmac-sha256 hex de "{timestamp}.{corpsBrut}">
 *
 * Corps : payload natif Nyole (event, data.id, data.amount en centimes,
 * data.currency, data.status). Le service normalise ensuite vers le format métier.
 */
class RecevoirWebhookPaiementController extends Controller
{
    public function __construct(
        private readonly RecevoirWebhookPaiementService $service
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $resultat = $this->service->recevoir(
            $request->getContent(),
            $request->header('X-Afriflow-Timestamp'),
            $request->header('X-Afriflow-Signature')
        );

        return response()->json([
            'message' => $resultat['message'],
        ]);
    }
}