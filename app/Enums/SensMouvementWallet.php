<?php

namespace App\Enums;

enum SensMouvementWallet: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}