<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuffelService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl    = config('services.duffel.base_url', 'https://api.duffel.com');
        $this->apiKey     = config('services.duffel.api_key', '');
        $this->apiVersion = config('services.duffel.api_version', 'v2');
    }

    // ──────────────────────────────────────────────────────────────
    //  FLIGHTS
    // ──────────────────────────────────────────────────────────────

    /**
     * Search for flight offers.
     *
     * @param  array{
     *   origin: string,
     *   destination: string,
     *   depart: string,
     *   return_date: string|null,
     *   adults: int,
     *   cabin: string,
     *   currency: string,
     *   trip_type: string,
     * } $params
     * @return array{offers: array, meta: array}
     */
    public function searchFlights(array $params): array
    {
        // Build slices (one-way or round-trip)
        $slices = [
            [
                'origin'         => $params['origin'],
                'destination'    => $params['destination'],
                'departure_date' => $params['depart'],
            ],
        ];

        if (($params['trip_type'] ?? 'one-way') === 'round-trip' && !empty($params['return_date'])) {
            $slices[] = [
                'origin'         => $params['destination'],
                'destination'    => $params['origin'],
                'departure_date' => $params['return_date'],
            ];
        }

        // Build passengers array
        $passengers = array_fill(0, (int) ($params['adults'] ?? 1), ['type' => 'adult']);

        // Map cabin class to Duffel format
        $cabinMap = [
            'ECONOMY'         => 'economy',
            'PREMIUM_ECONOMY' => 'premium_economy',
            'BUSINESS'        => 'business',
            'FIRST'           => 'first',
        ];
        $cabinClass = $cabinMap[$params['cabin'] ?? 'ECONOMY'] ?? 'economy';

        // Cache key — don't hammer the API for the same search
        $cacheKey = 'duffel_search_' . md5(json_encode([$slices, $passengers, $cabinClass]));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($slices, $passengers, $cabinClass, $params) {
            $response = $this->post('/air/offer_requests', [
                'data' => [
                    'slices'       => $slices,
                    'passengers'   => $passengers,
                    'cabin_class'  => $cabinClass,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Duffel offer_request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                // Fall back to mock data in development
                return $this->mockFlights($params);
            }

            $offerRequest = $response->json('data');
            $offers       = $offerRequest['offers'] ?? [];

            return [
                'offers'   => $this->normaliseOffers($offers, $params['currency'] ?? 'USD'),
                'meta'     => [
                    'offer_request_id' => $offerRequest['id'] ?? null,
                    'total'            => count($offers),
                ],
            ];
        });
    }

    /**
     * Get a single offer by ID (needed before booking).
     */
    public function getOffer(string $offerId): array
    {
        $response = $this->get("/air/offers/{$offerId}");

        if (!$response->successful()) {
            Log::error('Duffel get offer failed', ['offer_id' => $offerId]);
            return [];
        }

        return $response->json('data') ?? [];
    }

    // ──────────────────────────────────────────────────────────────
    //  DATA NORMALISATION
    //  Converts Duffel's response shape into a flat array our
    //  Vue components can consume directly.
    // ──────────────────────────────────────────────────────────────

    private function normaliseOffers(array $offers, string $currency): array
    {
        return collect($offers)
            ->map(fn (array $offer) => $this->normaliseOffer($offer, $currency))
            ->sortBy('price')
            ->values()
            ->all();
    }

    private function normaliseOffer(array $offer, string $currency): array
    {
        $slice    = $offer['slices'][0] ?? [];
        $segments = $slice['segments'] ?? [];
        $first    = $segments[0] ?? [];
        $last     = end($segments) ?: [];

        $stops = count($segments) - 1;

        return [
            'id'             => $offer['id'],
            'price'          => (float) ($offer['total_amount'] ?? 0),
            'currency'       => $offer['total_currency'] ?? $currency,
            'airline_name'   => $first['marketing_carrier']['name'] ?? '',
            'airline_iata'   => $first['marketing_carrier']['iata_code'] ?? '',
            'flight_number'  => ($first['marketing_carrier']['iata_code'] ?? '') . ($first['marketing_carrier_flight_designation'] ?? ''),
            'origin'         => $first['origin']['iata_code'] ?? '',
            'origin_name'    => $first['origin']['name'] ?? '',
            'destination'    => $last['destination']['iata_code'] ?? '',
            'destination_name' => $last['destination']['name'] ?? '',
            'departs_at'     => $first['departing_at'] ?? '',
            'arrives_at'     => $last['arriving_at'] ?? '',
            'duration'       => $slice['duration'] ?? '',
            'stops'          => $stops,
            'stop_label'     => match (true) {
                $stops === 0 => 'Direct',
                $stops === 1 => '1 stop',
                default      => "{$stops} stops",
            },
            'cabin_class'    => $first['passengers'][0]['cabin_class'] ?? '',
            'baggages'       => $this->parseBaggages($offer),
            'expires_at'     => $offer['expires_at'] ?? '',
            'raw'            => $offer, // keep full payload for booking step
        ];
    }

    private function parseBaggages(array $offer): array
    {
        $conditions = $offer['conditions'] ?? [];

        return [
            'carry_on'  => ($conditions['carry_on_baggage']['allowed'] ?? null) === true,
            'checked'   => ($conditions['checked_baggage']['maximum_quantity'] ?? 0) > 0,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  MOCK DATA  (used when API key is not set or in development)
    // ──────────────────────────────────────────────────────────────

    public function mockFlights(array $params): array
    {
        $airlines = [
            ['name' => 'Emirates',          'iata' => 'EK'],
            ['name' => 'British Airways',   'iata' => 'BA'],
            ['name' => 'Qatar Airways',     'iata' => 'QR'],
            ['name' => 'Lufthansa',         'iata' => 'LH'],
            ['name' => 'Air France',        'iata' => 'AF'],
            ['name' => 'Turkish Airlines',  'iata' => 'TK'],
            ['name' => 'Ethiopian Airlines','iata' => 'ET'],
            ['name' => 'Kenya Airways',     'iata' => 'KQ'],
            ['name' => 'Arik Air',          'iata' => 'W3'],
        ];

        $count   = rand(6, 12);
        $basePrice = rand(150, 400);
        $offers  = [];

        for ($i = 0; $i < $count; $i++) {
            $airline  = $airlines[array_rand($airlines)];
            $stops    = rand(0, 2);
            $hours    = rand(2, 14);
            $minutes  = rand(0, 59);
            $price    = $basePrice + ($i * rand(15, 60)) + ($stops * rand(20, 80));
            $depHour  = rand(5, 22);
            $depDate  = $params['depart'] ?? now()->addDays(7)->toDateString();

            $offers[] = [
                'id'              => 'mock_' . uniqid(),
                'price'           => (float) $price,
                'currency'        => $params['currency'] ?? 'USD',
                'airline_name'    => $airline['name'],
                'airline_iata'    => $airline['iata'],
                'flight_number'   => $airline['iata'] . rand(100, 999),
                'origin'          => $params['origin'] ?? 'LHR',
                'origin_name'     => $params['origin'] ?? 'London',
                'destination'     => $params['destination'] ?? 'DXB',
                'destination_name'=> $params['destination'] ?? 'Dubai',
                'departs_at'      => "{$depDate}T" . str_pad($depHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                'arrives_at'      => "{$depDate}T" . str_pad(($depHour + $hours) % 24, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':00',
                'duration'        => "PT{$hours}H{$minutes}M",
                'stops'           => $stops,
                'stop_label'      => match (true) {
                    $stops === 0 => 'Direct',
                    $stops === 1 => '1 stop',
                    default      => "{$stops} stops",
                },
                'cabin_class'     => strtolower($params['cabin'] ?? 'economy'),
                'baggages'        => ['carry_on' => true, 'checked' => $stops > 0],
                'expires_at'      => now()->addHours(6)->toISOString(),
                'raw'             => [],
            ];
        }

        // sort by price
        usort($offers, fn ($a, $b) => $a['price'] <=> $b['price']);

        return [
            'offers' => $offers,
            'meta'   => ['offer_request_id' => null, 'total' => count($offers), 'is_mock' => true],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  MOCK HOTELS
    // ──────────────────────────────────────────────────────────────

    public function mockHotels(array $params): array
    {
        $names = [
            'Grand Palace', 'The Riverside', 'Skyline Suites', 'Royal Garden',
            'Urban Oasis', 'Boutique Centrale', 'The Meridian', 'Harbour View',
            'Sunset Boulevard', 'The Pinnacle', 'Azure Heights', 'Clifton House',
            'The Carlton', 'Bloom Hotel', 'Anchor & Bay',
        ];

        $types     = ['hotel', 'hotel', 'hotel', 'apartment', 'villa', 'resort', 'bnb', 'hostel'];
        $amenities = ['🛁 Pool', '📶 Free WiFi', '🅿️ Parking', '🍳 Breakfast', '💪 Gym', '🧖 Spa', '☕ Café', '🌿 Garden', '🚐 Shuttle'];
        $emojis    = ['🏨', '🏩', '🏰', '🏯', '🌴', '🌅', '🗼', '🌃', '🏔️', '🛳️'];
        $currency  = $params['currency'] ?? 'USD';

        $checkin  = $params['checkin']  ?? now()->addDays(7)->toDateString();
        $checkout = $params['checkout'] ?? now()->addDays(10)->toDateString();
        $nights   = max(1, (int) ceil((strtotime($checkout) - strtotime($checkin)) / 86400));
        $guests   = (int) ($params['guests'] ?? 1);
        $minStars = (int) ($params['stars'] ?? 0);

        $count  = rand(8, 14);
        $hotels = [];

        for ($i = 0; $i < $count; $i++) {
            $stars  = max($minStars, rand(2, 5));
            $base   = match ($stars) {
                5 => rand(200, 400),
                4 => rand(100, 200),
                3 => rand(55, 110),
                default => rand(25, 60),
            };
            $pricePerNight = (int) round($base / 5) * 5;
            $rating        = round(rand(75, 99) / 10, 1);
            $ratingLabel   = match (true) {
                $rating >= 9.0 => 'Exceptional',
                $rating >= 8.0 => 'Excellent',
                $rating >= 7.0 => 'Very Good',
                default        => 'Good',
            };

            shuffle($amenities);
            $hotelAmenities = array_slice($amenities, 0, rand(3, 6));

            $hotels[] = [
                'id'             => 'hotel_' . uniqid(),
                'name'           => $names[$i % count($names)] . ($i >= count($names) ? ' ' . ($i + 1) : ''),
                'destination'    => $params['destination'] ?? 'London',
                'type'           => $types[$i % count($types)],
                'stars'          => $stars,
                'rating'         => $rating,
                'rating_label'   => $ratingLabel,
                'price_per_night'=> $pricePerNight,
                'total_price'    => $pricePerNight * $nights,
                'currency'       => $currency,
                'nights'         => $nights,
                'guests'         => $guests,
                'amenities'      => $hotelAmenities,
                'free_cancel'    => (bool) rand(0, 1),
                'emoji'          => $emojis[$i % count($emojis)],
                'checkin'        => $checkin,
                'checkout'       => $checkout,
            ];
        }

        usort($hotels, fn ($a, $b) => $a['price_per_night'] <=> $b['price_per_night']);

        return [
            'hotels' => $hotels,
            'meta'   => [
                'total'       => count($hotels),
                'destination' => $params['destination'] ?? '',
                'checkin'     => $checkin,
                'checkout'    => $checkout,
                'nights'      => $nights,
                'guests'      => $guests,
                'is_mock'     => true,
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  HTTP HELPERS
    // ──────────────────────────────────────────────────────────────

    private function get(string $endpoint): Response
    {
        return Http::withHeaders($this->headers())
            ->get($this->baseUrl . $endpoint);
    }

    private function post(string $endpoint, array $body): Response
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . $endpoint, $body);
    }

    private function headers(): array
    {
        return [
            'Authorization'   => "Bearer {$this->apiKey}",
            'Duffel-Version'  => $this->apiVersion,
            'Accept'          => 'application/json',
            'Content-Type'    => 'application/json',
            'Accept-Encoding' => 'gzip',
        ];
    }
}
