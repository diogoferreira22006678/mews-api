<?php

namespace App\Mappers;

class MewsCustomerMapper
{
    public static function map(array $response): array
    {
        return collect($response['Customers'] ?? [])
            ->map(function ($item) {
                $customer = $item['Customer'] ?? [];

                return [
                    'customer_id' => $customer['Id'] ?? null,
                    'first_name'  => $customer['FirstName'] ?? null,
                    'last_name'   => $customer['LastName'] ?? null,
                    'email'       => $customer['Email'] ?? null,
                    'phone_number'=> $customer['Phone'] ?? null,
                    'nationality' => $customer['NationalityCode'] ?? null,
                    'language'    => $customer['PreferredLanguageCode'] ?? null,
                    'is_active'   => $customer['IsActive'] ?? null,
                    'created_at'  => $customer['CreatedUtc'] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}