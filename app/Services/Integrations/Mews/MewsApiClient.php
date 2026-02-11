<?php

namespace App\Services\Integrations\Mews;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class MewsApiClient
{
    protected string $baseUrl;
    protected string $clientToken;
    protected string $accessToken;
    protected string $clientName;

    public function __construct()
    {
        $this->baseUrl     = config('services.mews.url');
        $this->clientToken = config('services.mews.client_token');
        $this->accessToken = config('services.mews.access_token');
        $this->clientName  = config('services.mews.client_name', 'Laravel Mews API Test 1.0');
    }

    protected function post(string $endpoint, array $payload): array
    {
        $response = Http::post($this->baseUrl . $endpoint, array_merge([
            'ClientToken' => $this->clientToken,
            'AccessToken' => $this->accessToken,
            'Client'      => $this->clientName,
        ], $payload));

       if (! $response->successful()) {
            throw new \RuntimeException(
                'Mews API error',
                502
            );
        }

        return $response->json();
    }

    public function fetchReservations(array $filters): array
    {
        $payload = [
            'Limitation' => ['Count' => 50],
        ];

        if (!empty($filters['check_in'])) {
            $payload['ScheduledStartUtc'] = [
                'StartUtc' => Carbon::parse($filters['check_in'])->startOfDay()->toIso8601String(),
                'EndUtc'   => Carbon::parse($filters['check_out'] ?? $filters['check_in'])->endOfDay()->toIso8601String(),
            ];
        }

        if (!empty($filters['status'])) {
            $payload['States'] = [$filters['status']];
        }

        $response = $this->post(
            '/api/connector/v1/reservations/getAll/2023-06-06',
            $payload
        );

        return $response['Reservations'] ?? [];
    }

    public function fetchCustomersByIds(array $customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }

        $response = $this->post(
            '/api/connector/v1/customers/getAll',
            [
                'CustomerIds' => $customerIds,
                'Extent' => [
                    'Customers' => true,
                    'Addresses' => false,
                    'Documents' => false,
                ],
                'Limitation' => ['Count' => 100],
            ]
        );

        return $response['Customers'] ?? [];
    }

    public function searchCustomers(array $filters = []): array
    {
        return $this->post(
            '/api/connector/v1/customers/search',
            [
                'Name'       => $filters['name'] ?? null,
                'ResourceId' => $filters['resource_id'] ?? null,
                'Extent' => [
                    'Customers' => true,
                    'Documents' => false,
                    'Addresses' => false,
                ],
            ]
        );
    }
}