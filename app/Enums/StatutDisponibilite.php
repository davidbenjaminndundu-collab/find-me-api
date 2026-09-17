<?php

namespace App\Enums;

enum StatutDisponibilite: string
{
    case Disponible = 'disponible';
    case Occupe = 'occupe';
    case Indisponible = 'indisponible';
}