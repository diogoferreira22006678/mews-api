<?php

namespace App\Http\Controllers;

use App\Services\Reservations\MewsReservationService as MewsService;
use Illuminate\Http\Request;
use App\Mappers\MewsReservationMapper;

class MewsReservationsController extends Controller
{
    public function __construct(private MewsService $mews) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'string'],
            'check_in'    => ['required', 'date'],
            'check_out'   => ['nullable', 'date', 'after_or_equal:check_in'],
            'status'      => ['nullable', 'string'],
        ]);

        $result = $this->mews->getReservations($data);

        return response()->json([
            'property_id' => $data['property_id'],
            'check_in'    => $data['check_in'],
            'status'      => $data['status'] ?? null,
            'reservations'=> MewsReservationMapper::map(
                $result['reservations'],
                $result['customers']
            ),
        ]);
    }
}