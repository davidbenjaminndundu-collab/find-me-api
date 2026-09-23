<?php

namespace App\Enums;

enum StatutCompte: string
{
    case AVerifier = 'a_verifier';
    case Actif = 'actif';
    case Suspendu = 'suspendu';
    case Bloque = 'bloque';
    case Ferme = 'ferme';
}
