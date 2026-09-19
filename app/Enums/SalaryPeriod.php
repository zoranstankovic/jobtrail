<?php

namespace App\Enums;

enum SalaryPeriod: string implements HasLabel
{
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Yearly => 'Yearly',
            self::Monthly => 'Monthly',
            self::Hourly => 'Hourly',
        };
    }
}
