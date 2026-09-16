<?php

namespace App\Enums;

enum StatutCompte
{
    case AVerifier = 'a_verifier';
    case Actif = 'actif';
    case Suspendu = 'suspendu';
    case Bloque = 'bloque';
    case Ferme = 'ferme';
}
