<?php

namespace App\Enums;

enum StatutLivraison: string
{
    case Soumise = 'soumise';
    case Acceptee = 'acceptee';
    case AReviser = 'a_reviser';
    case Remplacee = 'remplacee';
}