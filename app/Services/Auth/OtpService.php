<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private const TTL_SECONDES = 600;

    private const CONTEXTE_VERIFICATION_TELEPHONE = 'verification_telephone';

    public function generer(
        string $telephone,
        string $contexte = self::CONTEXTE_VERIFICATION_TELEPHONE
    ): string {
        $code = (string) random_int(100000, 999999);

        Cache::store('redis')->put(
            $this->cle($telephone, $contexte),
            Hash::make($code),
            self::TTL_SECONDES
        );

        return $code;
    }

    public function estValide(
        string $telephone,
        string $code,
        string $contexte = self::CONTEXTE_VERIFICATION_TELEPHONE
    ): bool {
        $codeHash = Cache::store('redis')->get(
            $this->cle($telephone, $contexte)
        );

        if (! $codeHash) {
            return false;
        }

        return Hash::check($code, $codeHash);
    }

    public function supprimer(
        string $telephone,
        string $contexte = self::CONTEXTE_VERIFICATION_TELEPHONE
    ): void {
        Cache::store('redis')->forget(
            $this->cle($telephone, $contexte)
        );
    }

    private function cle(string $telephone, string $contexte): string
    {
        return "otp:{$contexte}:{$telephone}";
    }
}