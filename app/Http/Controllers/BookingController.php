<?php

namespace App\Http\Controllers;

use App\Models\SavedFlight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * GET /bookings — authenticated user's booking history
     */
    public function index(): Response
    {
        $bookings = auth()->user()
            ->bookings()
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'id'          => $b->id,
                'reference'   => $b->reference,
                'type'        => $b->type,
                'origin'      => $b->origin,
                'destination' => $b->destination,
                'depart_at'   => $b->depart_at?->toISOString(),
                'passengers'  => $b->passengers,
                'cabin_class' => $b->cabin_class,
                'total_price' => (float) $b->total_price,
                'currency'    => $b->currency,
                'status'      => $b->status,
                'created_at'  => $b->created_at->toISOString(),
                'airline'     => $b->raw_response['offer']['airline_name'] ?? null,
                'flight_no'   => $b->raw_response['offer']['flight_number'] ?? null,
            ]);

        return Inertia::render('bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * POST /saved-flights — toggle a flight saved/unsaved
     */
    public function saveToggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'origin'      => ['required', 'string', 'size:3'],
            'destination' => ['required', 'string', 'size:3'],
            'depart_at'   => ['required', 'date'],
            'price'       => ['required', 'numeric'],
            'currency'    => ['required', 'string'],
            'flight_data' => ['required', 'array'],
        ]);

        $user = auth()->user();

        $existing = $user->savedFlights()
            ->where('origin', $validated['origin'])
            ->where('destination', $validated['destination'])
            ->whereDate('depart_at', $validated['depart_at'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $user->savedFlights()->create($validated);
        }

        return back();
    }

    /**
     * GET /saved-flights — authenticated user's saved flights
     */
    public function saved(): Response
    {
        $saved = auth()->user()
            ->savedFlights()
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'origin'      => $s->origin,
                'destination' => $s->destination,
                'depart_at'   => $s->depart_at?->toISOString(),
                'price'       => (float) $s->price,
                'currency'    => $s->currency,
                'flight_data' => $s->flight_data,
                'created_at'  => $s->created_at->toISOString(),
            ]);

        return Inertia::render('bookings/Saved', [
            'savedFlights' => $saved,
        ]);
    }
}
