<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Customers\MewsCustomerService as MewsService;
use App\Mappers\MewsCustomerMapper;

class MewsCustomersController extends Controller
{
    public function __construct(private MewsService $mews) {}

    public function search(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string'],
            'resource_id' => ['nullable', 'string'],
        ]);

        $response = $this->mews->searchCustomers($data);

        return response()->json([
            'customers' => MewsCustomerMapper::map($response),
        ]);
    }
}