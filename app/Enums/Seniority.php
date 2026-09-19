<?php

namespace App\Enums;

enum Seniority: string implements HasLabel
{
    case Intern = 'intern';
    case Junior = 'junior';
    case Mid = 'mid';
    case Senior = 'senior';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::Intern => 'Intern',
            self::Junior => 'Junior',
            self::Mid => 'Mid-level',
            self::Senior => 'Senior',
            self::Lead => 'Lead',
        };
    }
}
