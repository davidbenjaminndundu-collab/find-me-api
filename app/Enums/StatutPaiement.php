<?php

namespace App\Enums;

enum StatutPaiement: string
{
    case Cree = 'cree';
    case EnAttente = 'en_attente';
    case Confirme = 'confirme';
    case Echoue = 'echoue';
    case Expire = 'expire';
    case Rembourse = 'rembourse';
}