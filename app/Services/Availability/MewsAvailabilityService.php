<?php

namespace App\Services\Availability;

use Illuminate\Support\Carbon;
use App\Services\Reservations\MewsReservationService as MewsService;
class MewsAvailabilityService
{
    public function __construct(
        private MewsService $mews
    ) {}

    public function getAvailability(array $data): array
    {
        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights   = $checkIn->diffInDays($checkOut);

        // 1. Buscar reservas que intersectam o período
        $reservationsData = $this->mews->getReservations([
            'check_in'  => $data['check_in'],
            'check_out' => $data['check_out'],
        ]);

        $reservations = $reservationsData['reservations'] ?? [];

        // 2. Inventário base (assunção documentada)
        $inventory = $this->roomInventory();

        // 3. Calcular ocupação real
        $occupied = $this->calculateOccupancy(
            $reservations,
            $checkIn,
            $checkOut
        );

        // 4. Montar resposta
        $rooms = [];

        foreach ($inventory as $roomType => $room) {
            // capacidade
            if ($room['capacity'] < $data['adults']) {
                continue;
            }

            $occupiedCount = $occupied[$roomType] ?? 0;
            $available     = max(0, $room['total'] - $occupiedCount);

            if ($available === 0) {
                continue;
            }

            $rooms[] = [
                'room_description' => $roomType,
                'price'    => $room['price'] * $nights,
                'currency' => 'EUR',
            ];
        }

        return [
            'property_id' => $data['property_id'],
            'check_in'    => $checkIn->toDateString(),
            'check_out'   => $checkOut->toDateString(),
            'nights'      => $nights,
            'adults'      => $data['adults'],
            'currency'    => 'EUR',
            'rooms'       => $rooms,
        ];
    }

    /**
     * Inventário base do hotel
     * Assunção explícita para o challenge
     */
    protected function roomInventory(): array
    {
        return [
            'Standard Double Room' => [
                'total'    => 10,
                'capacity' => 2,
                'price'    => 90,
            ],
            'Superior Double Room' => [
                'total'    => 5,
                'capacity' => 2,
                'price'    => 110,
            ],
        ];
    }

    /**
     * Calcula ocupação real com base nas reservas
     */
    protected function calculateOccupancy(
        array $reservations,
        Carbon $checkIn,
        Carbon $checkOut
    ): array {
        $occupied = [];

        foreach ($reservations as $reservation) {

            // 1. Ignorar reservas que não ocupam quarto
            if (!in_array($reservation['State'], ['Started', 'Processed', 'Confirmed'])) {
                continue;
            }

            $resStart = Carbon::parse($reservation['ScheduledStartUtc']);
            $resEnd   = Carbon::parse($reservation['ScheduledEndUtc']);

            // 2. Regra correta de interseção
            if ($resStart >= $checkOut || $resEnd <= $checkIn) {
                continue;
            }

            // 3. Tipo de quarto (fallback defensivo)
            $roomType = $reservation['ServiceName'] ?? 'Standard Double Room';

            $occupied[$roomType] = ($occupied[$roomType] ?? 0) + 1;
        }

        return $occupied;
    }
}