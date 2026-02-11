<?php

namespace App\Services\Reservations;

use App\Services\Integrations\Mews\MewsApiClient;

class MewsReservationService
{
    public function __construct(
        private MewsApiClient $client
    ) {}

    public function getReservations(array $filters): array
    {
        $reservations = $this->client->fetchReservations($filters);

        $customerIds = collect($reservations)
            ->pluck('AccountId')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $customers = $this->client->fetchCustomersByIds($customerIds);

        return [
            'reservations' => $reservations,
            'customers'    => $customers,
        ];
    }
}