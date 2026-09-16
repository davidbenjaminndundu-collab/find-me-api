<?php

namespace App\Enums;

enum CanalNotification: string
{
    case InApp = 'in_app';
    case Sms = 'sms';
    case Email = 'email';
}