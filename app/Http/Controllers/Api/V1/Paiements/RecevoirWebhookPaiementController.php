<?php

namespace App\Http\Controllers\Api\V1\Paiements;

use App\Http\Controllers\Controller;
use App\Services\Paiements\RecevoirWebhookPaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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