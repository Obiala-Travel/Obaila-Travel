<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowUpDown, PlaneTakeoff, Search, Users, Sparkles } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });
defineProps<{ canRegister?: boolean }>();

// ── Tab ───────────────────────────────────────────────
const activeTab = ref<'flights' | 'hotels'>('flights');

// ── Trip type ─────────────────────────────────────────
const tripType = ref<'one-way' | 'round-trip' | 'multi-city'>('one-way');

// ── Flight form ───────────────────────────────────────
const origin      = ref('');
const destination = ref('');
const departDate  = ref('');
const returnDate  = ref('');
const adults      = ref(1);
const cabinClass  = ref('ECONOMY');
const currency    = ref('USD');
const nearbyAirports = ref(false);

// ── Hotel form ────────────────────────────────────────
const hotelDest     = ref('');
const hotelCheckin  = ref('');
const hotelCheckout = ref('');
const hotelGuests   = ref(1);

// ── Airport autocomplete ──────────────────────────────
const AIRPORTS = [
    { code: 'LHR', city: 'London',        name: 'Heathrow',            country: 'GB' },
    { code: 'LGW', city: 'London',        name: 'Gatwick',             country: 'GB' },
    { code: 'MAN', city: 'Manchester',    name: 'Manchester',          country: 'GB' },
    { code: 'DUB', city: 'Dublin',        name: 'Dublin',              country: 'IE' },
    { code: 'CDG', city: 'Paris',         name: 'Charles de Gaulle',   country: 'FR' },
    { code: 'FRA', city: 'Frankfurt',     name: 'Frankfurt',           country: 'DE' },
    { code: 'AMS', city: 'Amsterdam',     name: 'Schiphol',            country: 'NL' },
    { code: 'MAD', city: 'Madrid',        name: 'Barajas',             country: 'ES' },
    { code: 'BCN', city: 'Barcelona',     name: 'El Prat',             country: 'ES' },
    { code: 'FCO', city: 'Rome',          name: 'Fiumicino',           country: 'IT' },
    { code: 'JFK', city: 'New York',      name: 'John F. Kennedy',     country: 'US' },
    { code: 'LAX', city: 'Los Angeles',   name: 'Los Angeles Intl',    country: 'US' },
    { code: 'ORD', city: 'Chicago',       name: "O'Hare",              country: 'US' },
    { code: 'MIA', city: 'Miami',         name: 'Miami Intl',          country: 'US' },
    { code: 'DXB', city: 'Dubai',         name: 'Dubai Intl',          country: 'AE' },
    { code: 'DOH', city: 'Doha',          name: 'Hamad Intl',          country: 'QA' },
    { code: 'SIN', city: 'Singapore',     name: 'Changi',              country: 'SG' },
    { code: 'NBO', city: 'Nairobi',       name: 'Jomo Kenyatta',       country: 'KE' },
    { code: 'LOS', city: 'Lagos',         name: 'Murtala Muhammed',    country: 'NG' },
    { code: 'ABV', city: 'Abuja',         name: 'Nnamdi Azikiwe',      country: 'NG' },
    { code: 'ACC', city: 'Accra',         name: 'Kotoka',              country: 'GH' },
    { code: 'JNB', city: 'Johannesburg',  name: 'OR Tambo',            country: 'ZA' },
    { code: 'CPT', city: 'Cape Town',     name: 'Cape Town Intl',      country: 'ZA' },
    { code: 'CAI', city: 'Cairo',         name: 'Cairo Intl',          country: 'EG' },
    { code: 'ADD', city: 'Addis Ababa',   name: 'Bole Intl',           country: 'ET' },
    { code: 'CMN', city: 'Casablanca',    name: 'Mohammed V',          country: 'MA' },
    { code: 'IST', city: 'Istanbul',      name: 'Istanbul',            country: 'TR' },
    { code: 'SYD', city: 'Sydney',        name: 'Kingsford Smith',     country: 'AU' },
    { code: 'YYZ', city: 'Toronto',       name: 'Pearson',             country: 'CA' },
];

const originQuery    = ref('');
const destQuery      = ref('');
const showOriginDrop = ref(false);
const showDestDrop   = ref(false);

const filteredOrigin = computed(() =>
    originQuery.value.length < 1 ? [] :
    AIRPORTS.filter(a =>
        a.city.toLowerCase().includes(originQuery.value.toLowerCase()) ||
        a.code.toLowerCase().includes(originQuery.value.toLowerCase()) ||
        a.name.toLowerCase().includes(originQuery.value.toLowerCase())
    ).slice(0, 6)
);

const filteredDest = computed(() =>
    destQuery.value.length < 1 ? [] :
    AIRPORTS.filter(a =>
        a.city.toLowerCase().includes(destQuery.value.toLowerCase()) ||
        a.code.toLowerCase().includes(destQuery.value.toLowerCase()) ||
        a.name.toLowerCase().includes(destQuery.value.toLowerCase())
    ).slice(0, 6)
);

function pickOrigin(ap: typeof AIRPORTS[0]) {
    origin.value      = ap.code;
    originQuery.value = `${ap.city} (${ap.code})`;
    showOriginDrop.value = false;
}
function pickDest(ap: typeof AIRPORTS[0]) {
    destination.value = ap.code;
    destQuery.value   = `${ap.city} (${ap.code})`;
    showDestDrop.value = false;
}
function swapAirports() {
    [origin.value, destination.value]    = [destination.value, origin.value];
    [originQuery.value, destQuery.value] = [destQuery.value, originQuery.value];
}

// ── Search ────────────────────────────────────────────
function searchFlights() {
    if (!origin.value || !destination.value || !departDate.value) return;
    router.get('/flights/search', {
        origin, destination,
        depart:      departDate.value,
        return_date: tripType.value === 'round-trip' ? returnDate.value : undefined,
        adults:      adults.value,
        cabin:       cabinClass.value,
        currency:    currency.value,
        trip_type:   tripType.value,
    });
}
function searchHotels() {
    if (!hotelDest.value || !hotelCheckin.value || !hotelCheckout.value) return;
    router.get('/hotels/search', {
        destination: hotelDest.value,
        checkin:     hotelCheckin.value,
        checkout:    hotelCheckout.value,
        guests:      hotelGuests.value,
    });
}

const todayStr = new Date().toISOString().split('T')[0];

const TRUST_BADGES = [
    { icon: '🔒', label: 'Secure booking' },
    { icon: '⚡', label: 'Real-time prices' },
    { icon: '✈️', label: '500+ airlines' },
    { icon: '💰', label: 'Best price guarantee' },
    { icon: '🌍', label: '9,000+ airports' },
    { icon: '📞', label: '24/7 support' },
];

const FEATURES = [
    { icon: '⚡', title: 'Lightning fast search',   desc: 'Results from 500+ airlines in under 2 seconds.' },
    { icon: '💰', title: 'Cheapest first, always',  desc: 'No paid placements — just the best deals ranked at the top.' },
    { icon: '🌍', title: 'Global coverage',         desc: '9,000+ airports worldwide. Domestic and international routes.' },
    { icon: '🔔', title: 'Price alerts',            desc: 'We watch your route 24/7 and email you the moment prices drop.' },
    { icon: '🔒', title: 'Secure checkout',         desc: 'PCI-DSS compliant with 3D Secure and full booking protection.' },
    { icon: '📱', title: 'Book anywhere',           desc: 'Fully responsive — search and book from any device.' },
];

const POPULAR_ROUTES = [
    { from: 'LHR', fromCity: 'London',         to: 'DXB', toCity: 'Dubai',     price: 320, color: '#0f4c81' },
    { from: 'LHR', fromCity: 'London',         to: 'JFK', toCity: 'New York',  price: 410, color: '#1a3a6b' },
    { from: 'LOS', fromCity: 'Lagos',          to: 'LHR', toCity: 'London',    price: 580, color: '#1a4731' },
    { from: 'NBO', fromCity: 'Nairobi',        to: 'DXB', toCity: 'Dubai',     price: 290, color: '#4a1942' },
    { from: 'ACC', fromCity: 'Accra',          to: 'CDG', toCity: 'Paris',     price: 490, color: '#7c2d12' },
    { from: 'JNB', fromCity: 'Johannesburg',   to: 'LHR', toCity: 'London',    price: 620, color: '#1e3a5f' },
];
</script>

<template>
    <Head title="Obiala — Find the Cheapest Flights" />

    <!-- ── HERO ──────────────────────────────────────────────── -->
    <section style="background: linear-gradient(160deg, #1a3f7a 0%, #1c64f2 55%, #0ea5e9 100%);"
             class="relative overflow-hidden pb-28 pt-16 text-white">
        <!-- Decorative blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -right-32 -top-32 h-80 w-80 rounded-full bg-white/5"></div>
            <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-white/5"></div>
        </div>

        <!-- Headline -->
        <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-medium backdrop-blur-sm">
                <Sparkles class="h-3.5 w-3.5" />
                500+ AIRLINES · REAL-TIME PRICES
            </div>
            <h1 class="text-5xl font-extrabold leading-tight tracking-tight sm:text-6xl" style="font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:-0.03em;">
                Search. Compare.<br />
                <span style="color: #bae6fd;">Fly for less.</span>
            </h1>
            <p class="mt-4 text-lg text-blue-100">
                Find the cheapest flights across hundreds of airlines —<br class="hidden sm:block" />
                always sorted lowest price first.
            </p>
        </div>

        <!-- ── Search Card ──────────────────────────────────── -->
        <div class="relative mx-auto mt-10 max-w-5xl px-4 sm:px-6">
            <div class="rounded-2xl bg-white p-6 text-gray-900 shadow-2xl">

                <!-- Tab bar: Flights / Hotels -->
                <div class="mb-5 flex border-b border-gray-100">
                    <button
                        @click="activeTab = 'flights'"
                        :class="activeTab === 'flights' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="flex items-center gap-2 border-b-2 px-4 pb-3 text-sm font-semibold transition"
                    >
                        <PlaneTakeoff class="h-4 w-4" /> Flights
                    </button>
                    <button
                        @click="activeTab = 'hotels'"
                        :class="activeTab === 'hotels' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="flex items-center gap-2 border-b-2 px-4 pb-3 text-sm font-semibold transition"
                    >
                        🏨 Hotels
                    </button>
                </div>

                <!-- ── FLIGHTS ─────────────────────────────── -->
                <div v-if="activeTab === 'flights'">
                    <!-- Trip type pills -->
                    <div class="mb-4 flex gap-2">
                        <button
                            v-for="t in [
                                { value: 'one-way',     label: 'One Way' },
                                { value: 'round-trip',  label: 'Round Trip' },
                                { value: 'multi-city',  label: 'Multi-City' },
                            ]"
                            :key="t.value"
                            @click="tripType = t.value as any"
                            :class="tripType === t.value
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        >
                            {{ t.label }}
                        </button>
                    </div>

                    <!-- Row 1: FROM · swap · TO · DEPART (+ RETURN if round-trip) -->
                    <div class="mb-3 flex items-end gap-2">
                        <!-- FROM -->
                        <div class="relative flex-1">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">From</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 focus-within:border-blue-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100">
                                <PlaneTakeoff class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                <input
                                    v-model="originQuery"
                                    @input="showOriginDrop = true"
                                    @blur="setTimeout(() => showOriginDrop = false, 150)"
                                    type="text"
                                    placeholder="City or airport"
                                    class="w-full bg-transparent text-sm outline-none placeholder:text-gray-400"
                                />
                            </div>
                            <!-- Dropdown -->
                            <div v-if="showOriginDrop && filteredOrigin.length"
                                 class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                <button
                                    v-for="ap in filteredOrigin" :key="ap.code"
                                    @mousedown="pickOrigin(ap)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-blue-50"
                                >
                                    <span class="w-10 rounded-md bg-gray-100 py-0.5 text-center text-xs font-bold text-gray-700">{{ ap.code }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ ap.city }}</p>
                                        <p class="text-xs text-gray-400">{{ ap.name }} · {{ ap.country }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Swap -->
                        <button
                            @click="swapAirports"
                            class="mb-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 shadow-sm transition hover:border-blue-400 hover:text-blue-600"
                        >
                            <ArrowUpDown class="h-4 w-4" />
                        </button>

                        <!-- TO -->
                        <div class="relative flex-1">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">To</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 focus-within:border-blue-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100">
                                <span class="text-gray-400">🔍</span>
                                <input
                                    v-model="destQuery"
                                    @input="showDestDrop = true"
                                    @blur="setTimeout(() => showDestDrop = false, 150)"
                                    type="text"
                                    placeholder="City or airport"
                                    class="w-full bg-transparent text-sm outline-none placeholder:text-gray-400"
                                />
                            </div>
                            <div v-if="showDestDrop && filteredDest.length"
                                 class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                <button
                                    v-for="ap in filteredDest" :key="ap.code"
                                    @mousedown="pickDest(ap)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-blue-50"
                                >
                                    <span class="w-10 rounded-md bg-gray-100 py-0.5 text-center text-xs font-bold text-gray-700">{{ ap.code }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ ap.city }}</p>
                                        <p class="text-xs text-gray-400">{{ ap.name }} · {{ ap.country }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- DEPART -->
                        <div class="w-40 flex-shrink-0">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Depart</label>
                            <input
                                v-model="departDate" :min="todayStr" type="date"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                            />
                        </div>

                        <!-- RETURN (round-trip only) -->
                        <div v-if="tripType === 'round-trip'" class="w-40 flex-shrink-0">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Return</label>
                            <input
                                v-model="returnDate" :min="departDate || todayStr" type="date"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                            />
                        </div>
                    </div>

                    <!-- Row 2: PASSENGERS · CABIN CLASS · CURRENCY · Search button -->
                    <div class="flex items-end gap-2">
                        <!-- PASSENGERS -->
                        <div class="w-44">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Passengers</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                <Users class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                <select v-model="adults" class="w-full bg-transparent text-sm outline-none">
                                    <option v-for="n in 9" :key="n" :value="n">{{ n }} {{ n === 1 ? 'Adult' : 'Adults' }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- CABIN CLASS -->
                        <div class="w-44">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Cabin Class</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                <span class="text-sm">🪑</span>
                                <select v-model="cabinClass" class="w-full bg-transparent text-sm outline-none">
                                    <option value="ECONOMY">Economy</option>
                                    <option value="PREMIUM_ECONOMY">Premium Economy</option>
                                    <option value="BUSINESS">Business</option>
                                    <option value="FIRST">First</option>
                                </select>
                            </div>
                        </div>

                        <!-- CURRENCY -->
                        <div class="w-44">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Currency</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                <span class="text-sm">💱</span>
                                <select v-model="currency" class="w-full bg-transparent text-sm outline-none">
                                    <option value="USD">USD — US Dollar</option>
                                    <option value="GBP">GBP — British Pound</option>
                                    <option value="EUR">EUR — Euro</option>
                                    <option value="NGN">NGN — Nigerian Naira</option>
                                    <option value="KES">KES — Kenyan Shilling</option>
                                    <option value="GHS">GHS — Ghanaian Cedi</option>
                                    <option value="ZAR">ZAR — South African Rand</option>
                                    <option value="AED">AED — UAE Dirham</option>
                                    <option value="CAD">CAD — Canadian Dollar</option>
                                    <option value="AUD">AUD — Australian Dollar</option>
                                </select>
                            </div>
                        </div>

                        <!-- Search button -->
                        <button
                            @click="searchFlights"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 active:scale-95"
                        >
                            <Search class="h-4 w-4" /> Search Flights
                        </button>
                    </div>

                    <!-- Nearby airports -->
                    <div class="mt-3 flex items-center gap-2">
                        <input v-model="nearbyAirports" id="nearby" type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                        <label for="nearby" class="cursor-pointer text-sm text-gray-500">Include nearby airports</label>
                    </div>
                </div>

                <!-- ── HOTELS ───────────────────────────────── -->
                <div v-if="activeTab === 'hotels'">
                    <!-- Row 1: DESTINATION -->
                    <div class="mb-3">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Destination</label>
                        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 focus-within:border-blue-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100">
                            <span class="text-gray-400">🏨</span>
                            <input
                                v-model="hotelDest" type="text" placeholder="City, hotel, or landmark"
                                class="w-full bg-transparent text-sm outline-none placeholder:text-gray-400"
                            />
                        </div>
                    </div>

                    <!-- Row 2: CHECK-IN · CHECK-OUT · GUESTS · Search -->
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Check-in</label>
                            <input v-model="hotelCheckin" :min="todayStr" type="date"
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100" />
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Check-out</label>
                            <input v-model="hotelCheckout" :min="hotelCheckin || todayStr" type="date"
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100" />
                        </div>
                        <div class="w-40">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Guests</label>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                <Users class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                <select v-model="hotelGuests" class="w-full bg-transparent text-sm outline-none">
                                    <option v-for="n in 8" :key="n" :value="n">{{ n }} {{ n === 1 ? 'Guest' : 'Guests' }}</option>
                                </select>
                            </div>
                        </div>
                        <button
                            @click="searchHotels"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 active:scale-95"
                        >
                            <Search class="h-4 w-4" /> Search Hotels
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Trust bar ──────────────────────────────────────────── -->
    <div class="border-b border-gray-100 bg-white py-4">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-x-8 gap-y-2 px-4">
            <span v-for="b in TRUST_BADGES" :key="b.label" class="flex items-center gap-1.5 text-sm text-gray-500">
                <span>{{ b.icon }}</span> {{ b.label }}
            </span>
        </div>
    </div>

    <!-- ── Popular routes ─────────────────────────────────────── -->
    <section class="bg-gray-50 py-14">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <h2 class="mb-1 text-2xl font-bold text-gray-900">Popular routes</h2>
            <p class="mb-7 text-sm text-gray-500">Top searched flights right now</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="r in POPULAR_ROUTES" :key="r.from + r.to"
                    @click="originQuery = `${r.fromCity} (${r.from})`; origin = r.from; destQuery = `${r.toCity} (${r.to})`; destination = r.to; activeTab = 'flights'; window.scrollTo({top:0,behavior:'smooth'})"
                    class="group relative h-44 overflow-hidden rounded-xl text-left transition hover:-translate-y-0.5 hover:shadow-xl"
                    :style="`background: ${r.color}`"
                >
                    <!-- dark gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/5 transition group-hover:from-black/80"></div>
                    <!-- arrow badge -->
                    <div class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm">→</div>
                    <!-- text -->
                    <div class="absolute bottom-0 left-0 right-0 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-white/70">{{ r.from }} → {{ r.to }}</p>
                        <p class="mt-1 text-base font-bold leading-tight text-white">{{ r.fromCity }} → {{ r.toCity }}</p>
                        <p class="mt-1 text-sm text-white/75">from ${{ r.price }}</p>
                    </div>
                </button>
            </div>
        </div>
    </section>

    <!-- ── Why Obiala ─────────────────────────────────────────── -->
    <section class="bg-white py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <div class="mb-2 text-center">
                <h2 class="text-2xl font-bold text-gray-900">Why millions choose Obiala</h2>
                <p class="mt-2 text-sm text-gray-500">We search every airline so you don't have to, and always put the cheapest option first.</p>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="f in FEATURES" :key="f.title"
                     class="rounded-xl border border-gray-100 bg-gray-50 p-5 transition hover:border-blue-200 hover:shadow-sm">
                    <div class="mb-3 text-2xl">{{ f.icon }}</div>
                    <h3 class="mb-1 font-semibold text-gray-900">{{ f.title }}</h3>
                    <p class="text-sm leading-relaxed text-gray-500">{{ f.desc }}</p>
                </div>
            </div>
        </div>
    </section>

</template>
