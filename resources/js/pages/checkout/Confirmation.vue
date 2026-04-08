<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, PlaneTakeoff, User, Mail, Phone } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface Passenger {
    title: string
    first_name: string
    last_name: string
    date_of_birth: string
    gender: string
    passport_number: string
    passport_expiry: string
    nationality: string
}

interface Contact {
    email: string
    phone: string
}

interface Offer {
    airline_name: string
    airline_iata: string
    flight_number: string
    origin: string
    origin_name: string
    destination: string
    destination_name: string
    departs_at: string
    arrives_at: string
    duration: string
    stops: number
    stop_label: string
    cabin_class: string
    price: number
    currency: string
}

interface Booking {
    reference: string
    type: string
    origin: string
    destination: string
    depart_at: string
    passengers: number
    cabin_class: string
    total_price: number
    currency: string
    status: string
    offer: Offer
    pax: Passenger[]
    contact: Contact
}

const props = defineProps<{ booking: Booking }>();

function formatTime(iso: string) {
    if (!iso) return '--:--';
    return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
}
function formatDate(iso: string) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}
function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price);
}
function parseDuration(iso: string) {
    const m = iso?.match(/PT(?:(\d+)H)?(?:(\d+)M)?/);
    if (!m) return iso ?? '';
    return `${m[1] ?? 0}h ${m[2] ?? 0}m`;
}
function airlineLogoUrl(iata: string) {
    return `https://pics.avs.io/60/60/${iata}.png`;
}

const cabinLabel: Record<string, string> = {
    economy: 'Economy', premium_economy: 'Premium Economy',
    business: 'Business', first: 'First Class',
    ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Premium Economy',
    BUSINESS: 'Business', FIRST: 'First Class',
};
</script>

<template>
    <Head :title="`Booking Confirmed — ${booking.reference} — Obiala`" />

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">

        <!-- ── Success header ──────────────────────────────────── -->
        <div class="mb-8 flex flex-col items-center text-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                <CheckCircle class="h-9 w-9 text-emerald-600" />
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Booking confirmed!
            </h1>
            <p class="mt-1.5 text-gray-500">
                Your booking reference is
                <span class="ml-1 rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-base font-bold tracking-widest text-gray-900">
                    {{ booking.reference }}
                </span>
            </p>
            <p class="mt-2 text-sm text-gray-400">
                A confirmation has been sent to
                <strong class="text-gray-600">{{ booking.contact?.email }}</strong>
            </p>
        </div>

        <!-- ── Flight summary ──────────────────────────────────── -->
        <div class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                <PlaneTakeoff class="h-4 w-4 text-blue-600" />
                <h2 class="font-semibold text-gray-900">Flight details</h2>
                <span :class="['ml-auto rounded-full px-2.5 py-0.5 text-xs font-semibold',
                               booking.status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                    {{ booking.status === 'confirmed' ? 'Confirmed' : 'Pending payment' }}
                </span>
            </div>

            <div class="px-5 py-4">
                <!-- Airline row -->
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-gray-50">
                        <img :src="airlineLogoUrl(booking.offer?.airline_iata ?? '')"
                             :alt="booking.offer?.airline_name"
                             class="h-full w-full object-contain p-1"
                             @error="($event.target as HTMLImageElement).style.display='none'" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ booking.offer?.airline_name }}</p>
                        <p class="text-xs text-gray-400">{{ booking.offer?.flight_number }}</p>
                    </div>
                </div>

                <!-- Route timeline -->
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ formatTime(booking.offer?.departs_at) }}</p>
                        <p class="text-sm font-semibold text-gray-600">{{ booking.offer?.origin }}</p>
                        <p class="text-xs text-gray-400">{{ booking.offer?.origin_name }}</p>
                    </div>
                    <div class="flex flex-col items-center gap-1 px-4">
                        <div class="h-px w-20 bg-gray-300 sm:w-32"></div>
                        <p class="text-xs text-gray-400">{{ parseDuration(booking.offer?.duration) }}</p>
                        <p class="text-xs text-gray-400">{{ booking.offer?.stop_label }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ formatTime(booking.offer?.arrives_at) }}</p>
                        <p class="text-sm font-semibold text-gray-600">{{ booking.offer?.destination }}</p>
                        <p class="text-xs text-gray-400">{{ booking.offer?.destination_name }}</p>
                    </div>
                </div>
                <p class="mt-2 text-center text-xs text-gray-400">{{ formatDate(booking.offer?.departs_at) }}</p>

                <!-- Details row -->
                <div class="mt-4 flex flex-wrap gap-3">
                    <span class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        {{ cabinLabel[booking.cabin_class] ?? booking.cabin_class }}
                    </span>
                    <span class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        {{ booking.passengers }} passenger{{ booking.passengers > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Passengers ──────────────────────────────────────── -->
        <div class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                <User class="h-4 w-4 text-blue-600" />
                <h2 class="font-semibold text-gray-900">Passenger{{ booking.pax?.length > 1 ? 's' : '' }}</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <div v-for="(pax, i) in booking.pax" :key="i" class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-semibold capitalize text-gray-900">
                            {{ pax.title }}. {{ pax.first_name }} {{ pax.last_name }}
                        </p>
                        <p class="text-xs text-gray-400">Passport: {{ pax.passport_number }} · Expires: {{ pax.passport_expiry }}</p>
                    </div>
                    <span class="text-xs text-gray-400">Adult</span>
                </div>
            </div>
        </div>

        <!-- ── Contact + Total ─────────────────────────────────── -->
        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <Mail class="h-4 w-4 text-blue-600" /> Contact
                </div>
                <p class="text-sm text-gray-600">{{ booking.contact?.email }}</p>
                <p class="text-sm text-gray-600">{{ booking.contact?.phone }}</p>
            </div>
            <div class="flex flex-col items-end justify-center rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-400">Total paid</p>
                <p class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    {{ formatPrice(booking.total_price, booking.currency) }}
                </p>
                <p class="text-xs text-gray-400">All taxes and fees included</p>
            </div>
        </div>

        <!-- ── What's next ─────────────────────────────────────── -->
        <div class="mb-8 rounded-xl border border-blue-100 bg-blue-50 p-5">
            <h3 class="mb-3 font-semibold text-blue-900">What happens next?</h3>
            <ol class="flex flex-col gap-2 text-sm text-blue-800">
                <li class="flex items-start gap-2"><span class="font-bold">1.</span> Check your inbox — a booking summary has been sent to your email.</li>
                <li class="flex items-start gap-2"><span class="font-bold">2.</span> Payment is processed securely. Your e-ticket will be issued once payment clears.</li>
                <li class="flex items-start gap-2"><span class="font-bold">3.</span> Check in online with the airline 24–48 hours before departure.</li>
            </ol>
        </div>

        <!-- ── Actions ─────────────────────────────────────────── -->
        <div class="flex flex-wrap justify-center gap-3">
            <Link href="/"
                  class="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Search more flights
            </Link>
            <Link href="/bookings"
                  class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                  style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                View my bookings
            </Link>
        </div>

    </div>
</template>
