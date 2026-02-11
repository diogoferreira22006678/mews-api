<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Services\Availability\MewsAvailabilityService;


class ChatbotAvailabilityService
{
    public function __construct(
        private MewsAvailabilityService $availability
    ) {}

    public function handleAvailabilityIntent(array $params): array
    {
        // Dialogflow normalmente devolve datas como struct { year, month, day } ou ISO
        $normalized = $this->normalizeParams($params);

        $v = Validator::make($normalized, [
            'property_id' => ['required', 'string'],
            'check_in'    => ['required', 'date'],
            'check_out'   => ['required', 'date', 'after:check_in'],
            'adults'      => ['required', 'integer', 'min:1'],
        ]);

        if ($v->fails()) {
            throw ValidationException::withMessages($v->errors()->toArray());
        }

        $result = $this->availability->getAvailability($v->validated());

        return $this->formatChatResponse($result);
    }

    private function normalizeParams(array $params): array
    {
        // Dates
        $checkIn = null;
        $checkOut = null;

        // Dialogflow @sys.date-period
        if (isset($params['date_period'])) {
            $checkIn  = $params['date_period']['startDate'] ?? null;
            $checkOut = $params['date_period']['endDate'] ?? null;
        }

        // Fallbacks (se usares parâmetros manuais)
        $checkIn  ??= $params['check_in']  ?? $params['checkin']  ?? null;
        $checkOut ??= $params['check_out'] ?? $params['checkout'] ?? null;

        return [
            // Se só tens uma property, isto DEVE ser fixo
            'property_id' => $params['property_id']
                ?? config('services.mews.property_id')
                ?? 'default_property',

            'check_in'  => $this->normalizeDate($checkIn),
            'check_out' => $this->normalizeDate($checkOut),

            // Dialogflow @sys.number
            'adults' => isset($params['adults'])
                ? (int) $params['adults']
                : (isset($params['number']) ? (int) $params['number'] : null),
        ];
    }

    private function normalizeDate($value): ?string
    {
        if (is_string($value) && $value !== '') {
            // ISO date
            return Carbon::parse($value)->toDateString();
        }

        if (is_array($value) && isset($value['year'], $value['month'], $value['day'])) {
            return Carbon::create((int)$value['year'], (int)$value['month'], (int)$value['day'])->toDateString();
        }

        return null;
    }

    private function formatChatResponse(array $availability): array
    {
        $rooms = $availability['rooms'] ?? [];
        $checkIn = $availability['check_in'];
        $checkOut = $availability['check_out'];

        if (count($rooms) === 0) {
            return [
                'text' => "Sorry — we have no rooms available from {$checkIn} to {$checkOut}.",
                'carousel' => [
                    'type' => 'carousel',
                    'items' => [],
                ],
            ];
        }

        $lines = [];
        foreach ($rooms as $r) {
            $price = $r['price'];
            $currency = $r['currency'] ?? $availability['currency'] ?? 'EUR';
            $lines[] = "- {$r['room_description']} – {$currency} " . number_format((float)$price, 2, '.', '');
        }

        $text = "Yes! We have " . count($rooms) . " room(s) available from {$checkIn} to {$checkOut}:\n" . implode("\n", $lines);

        $items = array_map(function ($r) {
            $currency = $r['currency'] ?? 'EUR';
            return [
                'title' => $r['room_description'],
                'subtitle' => "{$currency} " . number_format((float)$r['price'], 2, '.', '') . " for your stay",
            ];
        }, $rooms);

        return [
            'text' => $text,
            'carousel' => [
                'type' => 'carousel',
                'items' => $items,
            ],
        ];
    }
}