<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RenvoiOtpRequest;
use App\Services\Auth\RenvoiOtpService;
use Illuminate\Http\JsonResponse;

class RenvoiOtpController extends Controller
{
    public function __construct(
        private readonly RenvoiOtpService $service
    ) {
    }

    public function __invoke(RenvoiOtpRequest $request): JsonResponse
    {
        $this->service->renvoyer(
            $request->validated('telephone')
        );

        return response()->json([
            'message' => 'Un nouveau code OTP a été envoyé.',
        ]);
    }
}