<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

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
    //  STEP 3 — Validate passengers, create Booking + PaymentIntent
    // ──────────────────────────────────────────────────────────────

    public function storeFlight(Request $request): JsonResponse
    {
        $offer = session('pending_flight_offer');

        if (empty($offer)) {
            return response()->json(['error' => 'Session expired. Please search again.'], 422);
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
        $price     = (float) ($offer['price'] ?? 0);
        $currency  = strtolower($offer['currency'] ?? 'usd');

        // Create the booking (status: pending until payment confirmed)
        $booking = Booking::create([
            'user_id'      => auth()->id(),
            'type'         => 'flight',
            'reference'    => $reference,
            'origin'       => $offer['origin']      ?? null,
            'destination'  => $offer['destination'] ?? null,
            'depart_at'    => $offer['departs_at']  ?? null,
            'passengers'   => count($validated['passengers']),
            'cabin_class'  => $offer['cabin_class'] ?? null,
            'total_price'  => $price,
            'currency'     => strtoupper($currency),
            'status'       => 'pending',
            'raw_response' => [
                'offer'      => $offer,
                'passengers' => $validated['passengers'],
                'contact'    => [
                    'email' => $validated['contact_email'],
                    'phone' => $validated['contact_phone'],
                ],
            ],
        ]);

        // Create Stripe PaymentIntent
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $intent = PaymentIntent::create([
                'amount'   => (int) round($price * 100), // Stripe uses cents
                'currency' => $currency,
                'metadata' => [
                    'booking_reference' => $reference,
                    'booking_id'        => $booking->id,
                    'origin'            => $offer['origin']      ?? '',
                    'destination'       => $offer['destination'] ?? '',
                ],
                'receipt_email'             => $validated['contact_email'],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'reference'     => $reference,
            ]);
        } catch (ApiErrorException $e) {
            // Roll back the booking if Stripe fails
            $booking->delete();

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  STEP 4 — Payment complete (Stripe redirects here)
    //           Verify the PaymentIntent and confirm the booking
    // ──────────────────────────────────────────────────────────────

    public function completeFlight(Request $request): Response|RedirectResponse
    {
        $reference        = $request->query('reference');
        $paymentIntentId  = $request->query('payment_intent');

        $booking = Booking::where('reference', $reference)->first();

        if (! $booking) {
            return redirect('/')->with('error', 'Booking not found.');
        }

        // Verify payment with Stripe
        if ($paymentIntentId && config('services.stripe.secret')) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $intent = PaymentIntent::retrieve($paymentIntentId);

                if ($intent->status === 'succeeded') {
                    $booking->update([
                        'status'              => 'confirmed',
                        'provider_booking_id' => $paymentIntentId,
                    ]);
                }
            } catch (ApiErrorException $e) {
                // Leave status as pending — booking still exists
            }
        }

        // Clear session
        session()->forget('pending_flight_offer');

        return redirect()->route('booking.confirmation', ['reference' => $reference]);
    }

    // ──────────────────────────────────────────────────────────────
    //  STEP 5 — Confirmation page
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
                'total_price' => (float) $booking->total_price,
                'currency'    => $booking->currency,
                'status'      => $booking->status,
                'offer'       => $booking->raw_response['offer']      ?? [],
                'pax'         => $booking->raw_response['passengers'] ?? [],
                'contact'     => $booking->raw_response['contact']    ?? [],
            ],
        ]);
    }
}
