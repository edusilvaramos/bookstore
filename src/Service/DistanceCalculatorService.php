<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceCalculatorService
{
    private ?array $storeCoordinatesCache = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $orsApiKey,
        private string $storeAddress,
    ) {
    }

    public function calculateDistanceInKm(string $destinationAddress): float
    {
        if (empty(trim($this->orsApiKey))) {
            throw new \RuntimeException('ORS_API_KEY is missing.');
        }

        if (empty(trim($this->storeAddress))) {
            throw new \RuntimeException('STORE_ADDRESS is missing.');
        }

        if (empty(trim($destinationAddress))) {
            throw new \RuntimeException('Destination address is empty.');
        }

        $storeCoordinates = $this->getStoreCoordinates();
        $destinationCoordinates = $this->geocode($destinationAddress);

        $response = $this->httpClient->request(
            'POST',
            'https://api.openrouteservice.org/v2/directions/driving-car',
            [
                'headers' => [
                    'Authorization' => $this->orsApiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'coordinates' => [
                        [$storeCoordinates['lon'], $storeCoordinates['lat']],
                        [$destinationCoordinates['lon'], $destinationCoordinates['lat']],
                    ],
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode >= 400) {
            throw new \RuntimeException('Directions API error: ' . json_encode($data));
        }

        $distanceMeters = $data['routes'][0]['summary']['distance'] ?? null;

        if ($distanceMeters === null) {
            throw new \RuntimeException('Could not retrieve route distance. Response: ' . json_encode($data));
        }

        return round($distanceMeters / 1000, 2);
    }

    private function getStoreCoordinates(): array
    {
        if ($this->storeCoordinatesCache !== null) {
            return $this->storeCoordinatesCache;
        }

        $this->storeCoordinatesCache = $this->geocode($this->storeAddress);

        return $this->storeCoordinatesCache;
    }

    private function geocode(string $address): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://api.openrouteservice.org/geocode/search',
            [
                'headers' => [
                    'Authorization' => $this->orsApiKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'text' => $address,
                    'size' => 1,
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode >= 400) {
            throw new \RuntimeException('Geocode API error: ' . json_encode($data));
        }

        $features = $data['features'] ?? [];

        if (empty($features)) {
            throw new \RuntimeException(sprintf('Address not found: %s', $address));
        }

        $coordinates = $features[0]['geometry']['coordinates'] ?? null;

        if (!is_array($coordinates) || !isset($coordinates[0], $coordinates[1])) {
            throw new \RuntimeException(sprintf(
                'Invalid coordinates returned for address: %s. Response: %s',
                $address,
                json_encode($data)
            ));
        }

        return [
            'lon' => (float) $coordinates[0],
            'lat' => (float) $coordinates[1],
        ];
    }
}