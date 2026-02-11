<?php

namespace App\Services\Customers;

use App\Services\Integrations\Mews\MewsApiClient;

class MewsCustomerService
{
    public function __construct(
        private MewsApiClient $client
    ) {}

    public function searchCustomers(array $filters): array
    {
        return $this->client->searchCustomers($filters);
    }
}