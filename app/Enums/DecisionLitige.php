<?php

namespace App\Enums;

enum DecisionLitige: string
{
    case RemboursementTotal = 'remboursement_total';
    case PaiementTotal = 'paiement_total';
    case RepartitionPartielle = 'repartition_partielle';
}