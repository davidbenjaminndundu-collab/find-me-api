<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Client = 'client';
    case Prestataire = 'prestataire';
    case Administrateur = 'administrateur';
}
