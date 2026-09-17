<?php

namespace App\Enums;

enum SalaryPeriod: string
{
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Hourly = 'hourly';
}
