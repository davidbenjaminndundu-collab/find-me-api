<?php

namespace App\Enums;

enum RoleUtilisateur
{
    case Client = 'client';
    case Prestataire = 'prestataire';
    case Administrateur = 'administrateur';
}
