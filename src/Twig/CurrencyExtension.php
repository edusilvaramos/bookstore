<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CurrencyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('euro', [$this, 'formatEuro']),
        ];
    }

    public function formatEuro(null|int|float|string $cents): string
    {
        if ($cents === null || $cents === '') {
            return '0,00 €';
        }

        $amount = ((float) $cents) / 100;

        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
