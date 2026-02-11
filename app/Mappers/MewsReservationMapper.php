<?php

namespace App\Mappers;

use Illuminate\Support\Carbon;

class MewsReservationMapper
{
    public static function map(array $reservations, array $customers): array
    {
        $customersById = collect($customers)->keyBy('Id');

        return collect($reservations)
            ->map(function ($r) use ($customersById) {
                $customer = $customersById->get($r['AccountId']);

                return [
                    'reservation_id' => $r['Id'],
                    'status' => strtolower($r['State']),
                    'first_name' => $customer['FirstName'] ?? null,
                    'last_name' => $customer['LastName'] ?? null,
                    'email' => $customer['Email'] ?? null,
                    'phone_number' => $customer['Phone'] ?? null,
                    'booking_channel' => $r['Origin'],
                    'room_state' => $r['AssignedResourceId']
                        ? ($r['State'] === 'Started' ? 'checked-in' : 'assigned')
                        : 'unassigned',
                    'room_number' => null,
                    'room_type' => null,
                    'room_category' => null,
                    'check_in' => Carbon::parse($r['ScheduledStartUtc'])->toDateString(),
                    'check_out' => Carbon::parse($r['ScheduledEndUtc'])->toDateString(),
                ];
            })
            ->values()
            ->all();
    }
}