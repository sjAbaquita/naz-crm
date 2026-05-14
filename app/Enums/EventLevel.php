<?php

namespace App\Enums;

enum EventLevel: string
{
    case Danger = 'danger';
    case Success = 'success';
    case Primary = 'primary';
    case Warning = 'warning';
}
