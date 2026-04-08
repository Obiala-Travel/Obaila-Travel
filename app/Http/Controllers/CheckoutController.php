<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    //  STEP 1 — Store selected offer in session, redirect to form
    // ──────────────────────────────────────────────────────────────

    public function selectFlight(Request $request): RedirectResponse
    {
        $offer = $request->input('offer');

        if (empty($offer)) {
            return redirect('/')->with('error', 'No flight selected.');
        }

        session(['pending_flight_offer' => $offer]);

        return redirect()->route('checkout.flight');
    }

    // ──────────────────────────────────────────────────────────────
    //  STEP 2 — Show checkout form
    // ──────────────────────────────────────────────────────────────

    public function showFlight(): Response|RedirectResponse
    {
        $offer = session('pending_flight_offer');

        if (empty($offer)) {
            return redirect('/')->with('error', 'Session expired. Please search again.');
        }

        return Inertia::render('checkout/Flight', [
            'offer' => $offer,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  STEP 3 — Process booking (validate + save)
    // ──────────────────────────────────────────────────────────────

    public function storeFlight(Request $request): RedirectResponse
    {
        $offer = session('pending_flight_offer');

        if (empty($offer)) {
            return redirect('/')->with('error', 'Session expired. Please search again.');
        }

        $validated = $request->validate([
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required', 'string', 'min:7', 'max:20'],
            'passengers'    => ['required', 'array', 'min:1'],

            'passengers.*.title'           => ['required', 'in:mr,mrs,ms,dr'],
            'passengers.*.first_name'      => ['required', 'string', 'max:60'],
            'passengers.*.last_name'       => ['required', 'string', 'max:60'],
            'passengers.*.date_of_birth'   => ['required', 'date', 'before:-2 years'],
            'passengers.*.gender'          => ['required', 'in:m,f'],
            'passengers.*.passport_number' => ['required', 'string', 'max:20'],
            'passengers.*.passport_expiry' => ['required', 'date', 'after:today'],
            'passengers.*.nationality'     => ['required', 'string', 'size:2'],
        ]);

        $reference = 'OB-' . strtoupper(Str::random(6));

        $booking = Booking::create([
            'user_id'     => auth()->id(), // null for guests
            'type'        => 'flight',
            'reference'   => $reference,
            'origin'      => $offer['origin']      ?? null,
            'destination' => $offer['destination'] ?? null,
            'depart_at'   => $offer['departs_at']  ?? null,
            'passengers'  => count($validated['passengers']),
            'cabin_class' => $offer['cabin_class'] ?? null,
            'total_price' => $offer['price']       ?? 0,
            'currency'    => $offer['currency']    ?? 'USD',
            'status'      => 'pending',
            'raw_response' => [
                'offer'      => $offer,
                'passengers' => $validated['passengers'],
                'contact'    => [
                    'email' => $validated['contact_email'],
                    'phone' => $validated['contact_phone'],
                ],
            ],
        ]);

        // Clear session so they can't double-submit
        session()->forget('pending_flight_offer');

        return redirect()->route('booking.confirmation', ['reference' => $reference]);
    }

    // ──────────────────────────────────────────────────────────────
    //  STEP 4 — Confirmation page
    // ──────────────────────────────────────────────────────────────

    public function confirmation(string $reference): Response|RedirectResponse
    {
        $booking = Booking::where('reference', $reference)->first();

        if (! $booking) {
            return redirect('/')->with('error', 'Booking not found.');
        }

        return Inertia::render('checkout/Confirmation', [
            'booking' => [
                'reference'   => $booking->reference,
                'type'        => $booking->type,
                'origin'      => $booking->origin,
                'destination' => $booking->destination,
                'depart_at'   => $booking->depart_at,
                'passengers'  => $booking->passengers,
                'cabin_class' => $booking->cabin_class,
                'total_price' => $booking->total_price,
                'currency'    => $booking->currency,
                'status'      => $booking->status,
                'offer'       => $booking->raw_response['offer']      ?? [],
                'pax'         => $booking->raw_response['passengers'] ?? [],
                'contact'     => $booking->raw_response['contact']    ?? [],
            ],
        ]);
    }
}
