<?php

namespace App\Enums;

enum StatutAnnonce: string
{
    case Brouillon = 'brouillon';
    case EnAttenteValidation = 'en_attente_validation';
    case Publiee = 'publiee';
    case Refusee = 'refusee';
    case Desactivee = 'desactivee';
    case Supprimee = 'supprimee';
}