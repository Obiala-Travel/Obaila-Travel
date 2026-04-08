<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlaneTakeoff, Hotel, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface Booking {
    id: number
    reference: string
    type: 'flight' | 'hotel'
    origin: string | null
    destination: string | null
    depart_at: string | null
    passengers: number
    cabin_class: string | null
    total_price: number
    currency: string
    status: 'pending' | 'confirmed' | 'cancelled'
    created_at: string
    airline: string | null
    flight_no: string | null
}

defineProps<{ bookings: Booking[] }>();

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

const statusStyle: Record<string, string> = {
    confirmed:  'bg-emerald-50 text-emerald-700 border-emerald-100',
    pending:    'bg-amber-50 text-amber-700 border-amber-100',
    cancelled:  'bg-red-50 text-red-600 border-red-100',
};

const cabinLabel: Record<string, string> = {
    ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Premium Economy',
    BUSINESS: 'Business', FIRST: 'First Class',
};
</script>

<template>
    <Head title="My Bookings — Obiala" />

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    My Bookings
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ bookings.length }} booking{{ bookings.length !== 1 ? 's' : '' }} total</p>
            </div>
            <Link href="/"
                  class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                + New search
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="bookings.length === 0"
             class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-20 text-center">
            <div class="mb-4 text-5xl">✈️</div>
            <p class="text-base font-bold text-gray-900">No bookings yet</p>
            <p class="mt-1 text-sm text-gray-500">Search for flights and book your first trip.</p>
            <Link href="/"
                  class="mt-5 rounded-xl px-6 py-2.5 text-sm font-semibold text-white"
                  style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                Search flights
            </Link>
        </div>

        <!-- Bookings list -->
        <div v-else class="flex flex-col gap-3">
            <article v-for="booking in bookings" :key="booking.id"
                     class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:border-blue-200 hover:shadow-sm">

                <!-- Type icon -->
                <div :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl',
                              booking.type === 'flight' ? 'bg-blue-50' : 'bg-purple-50']">
                    <PlaneTakeoff v-if="booking.type === 'flight'" class="h-5 w-5 text-blue-600" />
                    <Hotel v-else class="h-5 w-5 text-purple-600" />
                </div>

                <!-- Route + meta -->
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900">
                            {{ booking.origin ?? '—' }} → {{ booking.destination ?? '—' }}
                        </span>
                        <span :class="['rounded-full border px-2.5 py-0.5 text-[11px] font-semibold', statusStyle[booking.status]]">
                            {{ booking.status }}
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-400">
                        <span v-if="booking.airline">{{ booking.airline }} · {{ booking.flight_no }}</span>
                        <span v-if="booking.depart_at">
                            <Clock class="mr-0.5 inline h-3 w-3" />{{ formatDate(booking.depart_at) }}
                        </span>
                        <span v-if="booking.cabin_class">{{ cabinLabel[booking.cabin_class] ?? booking.cabin_class }}</span>
                        <span>{{ booking.passengers }} pax</span>
                    </div>
                </div>

                <!-- Price + reference -->
                <div class="flex flex-col items-end gap-1">
                    <span class="text-lg font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        {{ formatPrice(booking.total_price, booking.currency) }}
                    </span>
                    <span class="font-mono text-xs text-gray-400">{{ booking.reference }}</span>
                </div>

                <!-- View link -->
                <Link :href="`/booking/${booking.reference}`"
                      class="flex-shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                    View →
                </Link>
            </article>
        </div>

    </div>
</template>
