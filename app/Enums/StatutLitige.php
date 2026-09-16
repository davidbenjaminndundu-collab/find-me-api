<?php

namespace App\Enums;

enum StatutLitige: string
{
    case Ouvert = 'ouvert';
    case EnAnalyse = 'en_analyse';
    case InformationsDemandees = 'informations_demandees';
    case Resolu = 'resolu';
    case Ferme = 'ferme';
}