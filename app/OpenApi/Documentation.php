<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Find Me API',
    description: 'API REST du MVP Find Me, plateforme de services numériques.'
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Environnement local'
)]
class Documentation
{
}