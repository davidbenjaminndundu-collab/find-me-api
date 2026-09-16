<?php

namespace App\Enums;

enum TypeMouvementWallet: string
{
    case CreditCommande = 'credit_commande';
    case DebitRetrait = 'debit_retrait';
    case Remboursement = 'remboursement';
    case AjustementCredit = 'ajustement_credit';
    case AjustementDebit = 'ajustement_debit';
}