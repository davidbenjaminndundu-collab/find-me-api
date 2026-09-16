<?php

namespace App\Enums;

enum StatutRetrait: string
{
    case Demande = 'demande';
    case EnVerification = 'en_verification';
    case Approuve = 'approuve';
    case EnCoursExecution = 'en_cours_execution';
    case Execute = 'execute';
    case Echoue = 'echoue';
    case Refuse = 'refuse';
}