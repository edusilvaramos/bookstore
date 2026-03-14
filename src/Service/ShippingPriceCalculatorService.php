<?php

namespace App\Service;

class ShippingPriceCalculatorService
{
    public function calculateFromDistanceKm(float $distanceKm): int
    {
        if ($distanceKm <= 5) {
            return 890; // 8,90 €
        }

        if ($distanceKm <= 15) {
            return 1099; // 10,99 €
        }

        return 1590; // 15,90 €
    }
}