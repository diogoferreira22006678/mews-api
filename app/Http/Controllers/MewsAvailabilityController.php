<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Availability\MewsAvailabilityService;
use App\Mappers\MewsAvailabilityMapper;

class MewsAvailabilityController extends Controller
{
    public function __construct(
        private MewsAvailabilityService $availability
    ) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'string'],
            'check_in'    => ['required', 'date'],
            'check_out'   => ['required', 'date', 'after:check_in'],
            'adults'      => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->availability->getAvailability($data);

        return response()->json(
            MewsAvailabilityMapper::map($result)
        );
    }
}