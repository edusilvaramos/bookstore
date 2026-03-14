<?php

namespace App\Service;

use App\Entity\Address;
use Psr\Log\LoggerInterface;

class ShippingEstimatorService
{
    public function __construct(
        private DistanceCalculatorService $distanceCalculatorService,
        private ShippingPriceCalculatorService $shippingPriceCalculatorService,
        private LoggerInterface $logger,
    ) {
    }

    public function estimateForAddress(Address $address): array
    {
        try {
            $fullAddress = $address->getFullAddress();

            $distanceKm = $this->distanceCalculatorService->calculateDistanceInKm($fullAddress);
            $shippingCost = $this->shippingPriceCalculatorService->calculateFromDistanceKm($distanceKm);

            return [
                'distanceKm' => $distanceKm,
                'shippingCost' => $shippingCost,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Shipping estimation failed.', [
                'message' => $e->getMessage(),
                'address_id' => $address->getId(),
                'full_address' => method_exists($address, 'getFullAddress') ? $address->getFullAddress() : null,
                'exception_class' => $e::class,
            ]);

            return [
                'distanceKm' => null,
                'shippingCost' => null,
                'error' => 'Could not estimate shipping right now. Please verify the address and try again.',
            ];
        }
    }
}