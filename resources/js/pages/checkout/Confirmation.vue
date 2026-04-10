<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, PlaneTakeoff, User, Mail, ArrowRight, Home } from 'lucide-vue-next';
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

    <!-- ── Success hero band ─────────────────────────────────────────────── -->
    <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50">
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <div class="flex flex-col items-center text-center">
                <!-- Animated checkmark ring -->
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 shadow-md shadow-emerald-100">
                    <CheckCircle class="h-11 w-11 text-emerald-600" />
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900">Booking confirmed!</h1>
                <p class="mt-2 text-base text-gray-500">
                    Your e-ticket has been sent to
                    <strong class="text-gray-700">{{ booking.contact?.email }}</strong>
                </p>
                <!-- Reference pill -->
                <div class="mt-4 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-white px-5 py-2.5 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Booking ref</span>
                    <span class="font-mono text-lg font-bold tracking-widest text-gray-900">{{ booking.reference }}</span>
                    <span :class="[
                        'ml-2 rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide',
                        booking.status === 'confirmed'
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-amber-100 text-amber-700'
                    ]">
                        {{ booking.status === 'confirmed' ? 'Confirmed' : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Page body ─────────────────────────────────────────────────────── -->
    <div class="bg-gray-50 min-h-screen">
        <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 space-y-4">

            <!-- ── Flight summary card ─────────────────────────────────── -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <!-- Card header -->
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50">
                        <PlaneTakeoff class="h-4 w-4 text-blue-600" />
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Flight details</h2>
                </div>

                <div class="px-5 py-5">
                    <!-- Airline row -->
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 shadow-sm">
                            <img :src="airlineLogoUrl(booking.offer?.airline_iata ?? '')"
                                 :alt="booking.offer?.airline_name"
                                 class="h-full w-full object-contain p-1.5"
                                 @error="($event.target as HTMLImageElement).style.display='none'" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ booking.offer?.airline_name }}</p>
                            <p class="text-xs text-gray-400">{{ booking.offer?.flight_number }} · {{ cabinLabel[booking.cabin_class] ?? booking.cabin_class }}</p>
                        </div>
                        <div class="ml-auto flex flex-wrap gap-2">
                            <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                {{ booking.passengers }} passenger{{ booking.passengers > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <!-- Route timeline -->
                    <div class="rounded-xl bg-gray-50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="text-center">
                                <p class="text-2xl font-extrabold leading-none text-gray-900">{{ formatTime(booking.offer?.departs_at) }}</p>
                                <p class="mt-1 text-sm font-bold text-gray-600">{{ booking.offer?.origin }}</p>
                                <p class="text-xs text-gray-400">{{ booking.offer?.origin_name }}</p>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-1 px-2">
                                <span class="text-[11px] font-medium text-gray-400">{{ parseDuration(booking.offer?.duration) }}</span>
                                <div class="relative flex w-full items-center">
                                    <div class="h-px flex-1 bg-gray-300"></div>
                                    <PlaneTakeoff class="mx-1.5 h-3.5 w-3.5 flex-shrink-0 text-blue-400" />
                                    <div class="h-px flex-1 bg-gray-300"></div>
                                </div>
                                <span class="text-[11px] text-gray-400">{{ booking.offer?.stop_label }}</span>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-extrabold leading-none text-gray-900">{{ formatTime(booking.offer?.arrives_at) }}</p>
                                <p class="mt-1 text-sm font-bold text-gray-600">{{ booking.offer?.destination }}</p>
                                <p class="text-xs text-gray-400">{{ booking.offer?.destination_name }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-center text-xs text-gray-400">{{ formatDate(booking.offer?.departs_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Passengers card ────────────────────────────────────── -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50">
                        <User class="h-4 w-4 text-blue-600" />
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Passenger{{ booking.pax?.length > 1 ? 's' : '' }}</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="(pax, i) in booking.pax" :key="i" class="flex items-center justify-between px-5 py-3.5">
                        <div>
                            <p class="text-sm font-semibold capitalize text-gray-900">
                                {{ pax.title }}. {{ pax.first_name }} {{ pax.last_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">Passport: {{ pax.passport_number }} · Expires: {{ pax.passport_expiry }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Adult</span>
                    </div>
                </div>
            </div>

            <!-- ── Contact + Total row ────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50">
                            <Mail class="h-4 w-4 text-blue-600" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Contact</h3>
                    </div>
                    <p class="text-sm text-gray-600">{{ booking.contact?.email }}</p>
                    <p class="text-sm text-gray-600">{{ booking.contact?.phone }}</p>
                </div>
                <div class="flex flex-col justify-center rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total paid</p>
                    <p class="mt-1 text-3xl font-extrabold text-gray-900">
                        {{ formatPrice(booking.total_price, booking.currency) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">All taxes and fees included</p>
                </div>
            </div>

            <!-- ── What's next ────────────────────────────────────────── -->
            <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-bold text-blue-900">What happens next?</h3>
                <ol class="flex flex-col gap-3">
                    <li v-for="(step, i) in [
                        'Check your inbox — a booking summary has been sent to your email.',
                        'Payment is processed securely. Your e-ticket will be issued once payment clears.',
                        'Check in online with the airline 24–48 hours before departure.',
                    ]" :key="i" class="flex items-start gap-3">
                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white">
                            {{ i + 1 }}
                        </span>
                        <p class="text-sm text-blue-800">{{ step }}</p>
                    </li>
                </ol>
            </div>

            <!-- ── Actions ────────────────────────────────────────────── -->
            <div class="flex flex-wrap justify-center gap-3 pb-4">
                <Link href="/"
                      class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <Home class="h-4 w-4" /> Search more flights
                </Link>
                <Link href="/bookings"
                      class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                      style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                    View my bookings <ArrowRight class="h-4 w-4" />
                </Link>
            </div>

        </div>
    </div>
</template>
