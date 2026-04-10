<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Heart, PlaneTakeoff } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface SavedFlight {
    id: number
    origin: string
    destination: string
    depart_at: string | null
    price: number
    currency: string
    flight_data: Record<string, unknown>
    created_at: string
}

defineProps<{ savedFlights: SavedFlight[] }>();

function formatDate(iso: string | null) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency,
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(price);
}

function searchAgain(flight: SavedFlight) {
    router.get('/flights/search', {
        origin:      flight.origin,
        destination: flight.destination,
        depart:      flight.depart_at?.split('T')[0] ?? '',
        adults:      1,
        cabin:       'ECONOMY',
        currency:    flight.currency,
        trip_type:   'one-way',
    });
}

function remove(flight: SavedFlight) {
    router.post('/saved-flights', {
        origin:      flight.origin,
        destination: flight.destination,
        depart_at:   flight.depart_at,
        price:       flight.price,
        currency:    flight.currency,
        flight_data: flight.flight_data,
    });
}
</script>

<template>
    <Head title="Saved Flights — Obiala" />

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Saved Flights
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ savedFlights.length }} saved route{{ savedFlights.length !== 1 ? 's' : '' }}</p>
            </div>
            <Link href="/"
                  class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                + New search
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="savedFlights.length === 0"
             class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-20 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
                <Heart class="h-7 w-7 text-red-400" />
            </div>
            <p class="text-base font-bold text-gray-900">No saved flights yet</p>
            <p class="mt-1 text-sm text-gray-500">Tap the ❤ on any flight to save it for later.</p>
            <Link href="/"
                  class="mt-5 rounded-xl px-6 py-2.5 text-sm font-semibold text-white"
                  style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                Search flights
            </Link>
        </div>

        <!-- Saved flights list -->
        <div v-else class="flex flex-col gap-3">
            <article v-for="flight in savedFlights" :key="flight.id"
                     class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:border-blue-200 hover:shadow-sm">

                <!-- Icon -->
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <PlaneTakeoff class="h-5 w-5 text-blue-600" />
                </div>

                <!-- Route -->
                <div class="flex-1">
                    <p class="font-semibold text-gray-900">{{ flight.origin }} → {{ flight.destination }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">
                        Departs {{ formatDate(flight.depart_at) }} · Saved {{ formatDate(flight.created_at) }}
                    </p>
                </div>

                <!-- Price -->
                <span class="text-lg font-extrabold text-gray-900">
                    {{ formatPrice(flight.price, flight.currency) }}
                </span>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button @click="searchAgain(flight)"
                            class="rounded-lg px-4 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                        Search again
                    </button>
                    <button @click="remove(flight)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500">
                        <Heart class="h-4 w-4" fill="currentColor" />
                    </button>
                </div>
            </article>
        </div>

    </div>
</template>
