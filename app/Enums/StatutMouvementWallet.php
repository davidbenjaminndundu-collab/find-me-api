<?php

namespace App\Enums;

enum StatutMouvementWallet: string
{
    case EnAttente = 'en_attente';
    case Valide = 'valide';
    case Annule = 'annule';
    case Echoue = 'echoue';
}