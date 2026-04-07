<?php

namespace App\Http\Controllers;

use App\Services\DuffelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlightController extends Controller
{
    public function __construct(private DuffelService $duffel) {}

    /**
     * Handle flight search and return the results page.
     * GET /flights/search
     */
    public function search(Request $request): Response
    {
        $validated = $request->validate([
            'origin'      => ['required', 'string', 'size:3'],
            'destination' => ['required', 'string', 'size:3'],
            'depart'      => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:depart'],
            'adults'      => ['integer', 'min:1', 'max:9'],
            'cabin'       => ['in:ECONOMY,PREMIUM_ECONOMY,BUSINESS,FIRST'],
            'currency'    => ['in:USD,GBP,EUR,NGN,KES,GHS,ZAR,AED,CAD,AUD'],
            'trip_type'   => ['in:one-way,round-trip,multi-city'],
        ]);

        // Merge defaults for optional params
        $params = array_merge([
            'adults'    => 1,
            'cabin'     => 'ECONOMY',
            'currency'  => 'USD',
            'trip_type' => 'one-way',
        ], $validated);

        // If no API key yet, fall straight to mock data
        $results = empty(config('services.duffel.api_key'))
            ? $this->duffel->mockFlights($params)
            : $this->duffel->searchFlights($params);

        return Inertia::render('flights/Search', [
            'params'  => $params,
            'offers'  => $results['offers'],
            'meta'    => $results['meta'],
        ]);
    }
}
