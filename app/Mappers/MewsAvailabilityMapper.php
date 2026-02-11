<?php

namespace App\Mappers;

class MewsAvailabilityMapper
{
    public static function map(array $data): array
    {
        return [
            'property_id' => $data['property_id'],
            'check_in'    => $data['check_in'],
            'check_out'   => $data['check_out'],
            'nights'      => $data['nights'],
            'adults'      => $data['adults'],
            'currency'    => $data['currency'],
            'rooms'       => $data['rooms'],
        ];
    }
}