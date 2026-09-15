<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    #[OA\Get(
        path: '/health',
        summary: 'Vérifier l’état de l’API',
        description: 'Vérifie que le backend Find Me est opérationnel.',
        tags: ['Système'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API opérationnelle',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'API Find Me opérationnelle'
                        ),
                        new OA\Property(
                            property: 'service',
                            type: 'string',
                            example: 'find-me-api'
                        ),
                        new OA\Property(
                            property: 'version',
                            type: 'string',
                            example: '1.0.0'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API Find Me opérationnelle',
            'service' => 'find-me-api',
            'version' => '1.0.0',
        ]);
    }
}
