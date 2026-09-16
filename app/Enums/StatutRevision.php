<?php

namespace App\Enums;

enum StatutRevision: string
{
    case Demandee = 'demandee';
    case Acceptee = 'acceptee';
    case EnCours = 'en_cours';
    case Livree = 'livree';
    case Refusee = 'refusee';
    case Annulee = 'annulee';
}