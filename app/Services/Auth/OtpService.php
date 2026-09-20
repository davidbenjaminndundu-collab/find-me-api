<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private const TTL_SECONDES = 600;

    public function generer(string $telephone): string
    {
        $code = (string) random_int(100000, 999999);

        Cache::store('redis')->put(
            $this->cle($telephone),
            Hash::make($code),
            self::TTL_SECONDES
        );

        return $code;
    }

    public function estValide(string $telephone, string $code): bool
    {
        $hash = Cache::store('redis')->get($this->cle($telephone));

        if ($hash === null) {
            return false;
        }

        return Hash::check($code, $hash);
    }

    public function supprimer(string $telephone): void
    {
        Cache::store('redis')->forget($this->cle($telephone));
    }

    private function cle(string $telephone): string
    {
        return 'otp:telephone:'.$telephone;
    }
}