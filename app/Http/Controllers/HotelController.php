<?php

namespace App\Http\Controllers;

use App\Services\DuffelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function __construct(private DuffelService $duffel) {}

    /**
     * Handle hotel search and return the results page.
     * GET /hotels/search
     */
    public function search(Request $request): Response
    {
        $validated = $request->validate([
            'destination' => ['required', 'string', 'min:2', 'max:100'],
            'checkin'     => ['required', 'date', 'after_or_equal:today'],
            'checkout'    => ['required', 'date', 'after:checkin'],
            'guests'      => ['nullable', 'integer', 'min:1', 'max:20'],
            'rooms'       => ['nullable', 'integer', 'min:1', 'max:10'],
            'stars'       => ['nullable', 'integer', 'min:0', 'max:5'],
            'type'        => ['nullable', 'string', 'in:hotel,apartment,villa,hostel,resort,bnb'],
            'currency'    => ['nullable', 'in:USD,GBP,EUR,NGN,KES,GHS,ZAR,AED,CAD,AUD'],
        ]);

        $params = array_merge([
            'guests'   => 1,
            'rooms'    => 1,
            'stars'    => 0,
            'type'     => '',
            'currency' => 'USD',
        ], $validated);

        // Hotel search always uses mock for now — Duffel Stays API can be wired in later
        $results = $this->duffel->mockHotels($params);

        return Inertia::render('hotels/Search', [
            'params' => $params,
            'hotels' => $results['hotels'],
            'meta'   => $results['meta'],
        ]);
    }
}
