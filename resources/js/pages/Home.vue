<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowUpDown, PlaneTakeoff, Hotel, Search, Users, ChevronDown, MapPin, Calendar, Star, Shield, Zap, Globe, Bell, Plane, CheckCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });
defineProps<{ canRegister?: boolean }>();

// ── Tab ───────────────────────────────────────────────
const activeTab = ref<'flights' | 'hotels'>('flights');

// ── Trip type ─────────────────────────────────────────
const tripType = ref<'one-way' | 'round-trip' | 'multi-city'>('one-way');

// ── Flight form ───────────────────────────────────────
const origin         = ref('');
const destination    = ref('');
const departDate     = ref('');
const returnDate     = ref('');
const adults         = ref(1);
const cabinClass     = ref('ECONOMY');
const currency       = ref('USD');

// ── Hotel form ────────────────────────────────────────
const hotelDest      = ref('');
const hotelCheckin   = ref('');
const hotelCheckout  = ref('');
const hotelGuests    = ref(1);

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
    origin.value         = ap.code;
    originQuery.value    = `${ap.city} (${ap.code})`;
    showOriginDrop.value = false;
}
function pickDest(ap: typeof AIRPORTS[0]) {
    destination.value  = ap.code;
    destQuery.value    = `${ap.city} (${ap.code})`;
    showDestDrop.value = false;
}
function swapAirports() {
    [origin.value, destination.value]    = [destination.value, origin.value];
    [originQuery.value, destQuery.value] = [destQuery.value, originQuery.value];
}

const searchError = ref('');

function tryAutoMatch(query: string, setter: (ap: typeof AIRPORTS[0]) => void): boolean {
    if (!query) return false;
    const q = query.trim().toLowerCase();
    const exact = AIRPORTS.find(a => a.code.toLowerCase() === q);
    if (exact) { setter(exact); return true; }
    const cityMatches = AIRPORTS.filter(a => a.city.toLowerCase().includes(q));
    if (cityMatches.length === 1) { setter(cityMatches[0]); return true; }
    return false;
}

function searchFlights() {
    searchError.value = '';
    if (!origin.value) tryAutoMatch(originQuery.value, pickOrigin);
    if (!destination.value) tryAutoMatch(destQuery.value, pickDest);
    if (!origin.value)      { searchError.value = 'Please select a departure airport.'; return; }
    if (!destination.value) { searchError.value = 'Please select a destination airport.'; return; }
    if (!departDate.value)  { searchError.value = 'Please pick a departure date.'; return; }
    router.get('/flights/search', {
        origin:      origin.value,
        destination: destination.value,
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

// ── Static content ────────────────────────────────────
const STATS = [
    { value: '1M+',  label: 'Happy Travellers' },
    { value: '500+', label: 'Airlines Worldwide' },
    { value: '200+', label: 'Destinations' },
    { value: '4.8',  label: 'Average Rating', star: true },
];

const DESTINATIONS = [
    { city: 'Dubai',     country: 'UAE',          code: 'DXB', img: '/images/destination/destination-01.jpg' },
    { city: 'London',    country: 'United Kingdom', code: 'LHR', img: '/images/destination/destination-02.jpg' },
    { city: 'New York',  country: 'United States', code: 'JFK', img: '/images/destination/destination-03.jpg' },
    { city: 'Paris',     country: 'France',        code: 'CDG', img: '/images/destination/destination-04.jpg' },
    { city: 'Singapore', country: 'Singapore',     code: 'SIN', img: '/images/destination/destination-05.jpg' },
    { city: 'Lagos',     country: 'Nigeria',       code: 'LOS', img: '/images/destination/destination-06.jpg' },
];

const FEATURES = [
    { icon: Zap,    title: 'Lightning Fast Search',  desc: 'Live results from 500+ airlines in under 2 seconds. No waiting, no guessing.' },
    { icon: Globe,  title: 'Global Coverage',        desc: '9,000+ airports worldwide — domestic routes, international connections, and everything in between.' },
    { icon: Shield, title: 'Secure & Protected',     desc: 'PCI-DSS compliant checkout. Your payment data is encrypted end-to-end.' },
    { icon: Bell,   title: 'Price Alerts',           desc: 'We watch your route 24/7 and notify you the moment prices drop.' },
];

const STEPS = [
    { step: '01', icon: Search,   title: 'Search Flights',   desc: 'Enter your route, dates and number of passengers. We search hundreds of airlines instantly.' },
    { step: '02', icon: Users,    title: 'Fill In Details',  desc: 'Enter passenger information securely. It only takes a couple of minutes.' },
    { step: '03', icon: Plane,    title: 'Fly & Enjoy',      desc: 'Pay securely via Stripe. Your e-ticket lands in your inbox immediately.' },
];

const POPULAR_ROUTES = [
    { from: 'LHR', fromCity: 'London',       to: 'DXB', toCity: 'Dubai',     price: 320 },
    { from: 'LHR', fromCity: 'London',       to: 'JFK', toCity: 'New York',  price: 410 },
    { from: 'LOS', fromCity: 'Lagos',        to: 'LHR', toCity: 'London',    price: 580 },
    { from: 'NBO', fromCity: 'Nairobi',      to: 'DXB', toCity: 'Dubai',     price: 290 },
    { from: 'ACC', fromCity: 'Accra',        to: 'CDG', toCity: 'Paris',     price: 490 },
    { from: 'JNB', fromCity: 'Johannesburg', to: 'LHR', toCity: 'London',    price: 620 },
];

const TESTIMONIALS = [
    {
        quote: "Obiala made booking our Lagos to London trip effortless. The prices were better than anything I found elsewhere, and the e-ticket arrived instantly.",
        name: 'Amara O.',
        location: 'Lagos, Nigeria',
        rating: 5,
        img: '/images/users/user-01.jpg',
    },
    {
        quote: "I've used a lot of booking platforms, but Obiala is by far the cleanest experience. Search is fast, the checkout is simple, and I love the price alerts.",
        name: 'James K.',
        location: 'Nairobi, Kenya',
        rating: 5,
        img: '/images/users/user-28.jpg',
    },
];

function searchRoute(from: string, to: string) {
    origin.value      = from;
    destination.value = to;
    const found = AIRPORTS.find(a => a.code === from);
    if (found) originQuery.value = `${found.city} (${found.code})`;
    const foundTo = AIRPORTS.find(a => a.code === to);
    if (foundTo) destQuery.value = `${foundTo.city} (${foundTo.code})`;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<template>
    <Head title="Obiala Travel — Search & Book Flights" />

    <!-- ══════════════════════════════════════════════════
         HERO
    ══════════════════════════════════════════════════ -->
    <section class="relative min-h-[620px] overflow-hidden">
        <!-- Background image + overlays -->
        <div class="absolute inset-0">
            <img src="/images/banner/banner-06.jpg" alt="Travel"
                 class="h-full w-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1628]/90 via-[#0d2444]/80 to-[#1c3a6b]/70"></div>
        </div>

        <!-- Hero content -->
        <div class="relative z-10 mx-auto max-w-7xl px-4 pt-24 pb-16 sm:px-6 lg:px-8">
            <!-- Badge + headline -->
            <div class="mb-8 text-center">
                <span class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-blue-200 backdrop-blur-sm">
                    ✈ Powered by Duffel — Live Airline Data
                </span>
                <h1 class="mx-auto max-w-3xl text-4xl font-extrabold leading-tight text-white sm:text-5xl lg:text-6xl"
                    style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Your Next Journey<br />
                    <span class="text-blue-300">Starts Here.</span>
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-base text-blue-100/80">
                    Search live fares from 500+ airlines. Book in minutes. Fly with confidence.
                </p>
            </div>

            <!-- Search card -->
            <div class="mx-auto max-w-5xl rounded-2xl bg-white p-5 shadow-2xl">

                <!-- Tabs -->
                <div class="mb-5 flex gap-1 rounded-xl bg-gray-100 p-1 w-fit">
                    <button @click="activeTab = 'flights'"
                            :class="['flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold transition',
                                     activeTab === 'flights' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        <PlaneTakeoff class="h-4 w-4" /> Flights
                    </button>
                    <button @click="activeTab = 'hotels'"
                            :class="['flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold transition',
                                     activeTab === 'hotels' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        <Hotel class="h-4 w-4" /> Hotels
                    </button>
                </div>

                <!-- ── FLIGHT SEARCH ── -->
                <div v-if="activeTab === 'flights'">
                    <!-- Trip type -->
                    <div class="mb-4 flex gap-4">
                        <label v-for="t in [['one-way','One Way'],['round-trip','Round Trip'],['multi-city','Multi-City']]"
                               :key="t[0]" class="flex cursor-pointer items-center gap-1.5 text-sm font-medium text-gray-600">
                            <input type="radio" v-model="tripType" :value="t[0]"
                                   class="accent-blue-600" />
                            {{ t[1] }}
                        </label>
                    </div>

                    <!-- Search error -->
                    <div v-if="searchError" class="mb-3 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600">
                        {{ searchError }}
                    </div>

                    <!-- Inputs row -->
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_auto_1fr_1fr_1fr_auto]">

                        <!-- Origin -->
                        <div class="relative">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">From</label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="originQuery"
                                       @input="showOriginDrop = true; origin = ''"
                                       @focus="showOriginDrop = originQuery.length > 0"
                                       @blur="setTimeout(() => showOriginDrop = false, 150)"
                                       type="text" placeholder="City or airport"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                                <ul v-if="showOriginDrop && filteredOrigin.length"
                                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                    <li v-for="ap in filteredOrigin" :key="ap.code"
                                        @mousedown="pickOrigin(ap)"
                                        class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-sm hover:bg-blue-50">
                                        <span class="font-medium text-gray-800">{{ ap.city }}</span>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">{{ ap.code }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Swap button -->
                        <div class="flex items-end pb-0.5">
                            <button @click="swapAirports"
                                    class="flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition hover:border-blue-400 hover:text-blue-500">
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Destination -->
                        <div class="relative">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">To</label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="destQuery"
                                       @input="showDestDrop = true; destination = ''"
                                       @focus="showDestDrop = destQuery.length > 0"
                                       @blur="setTimeout(() => showDestDrop = false, 150)"
                                       type="text" placeholder="City or airport"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                                <ul v-if="showDestDrop && filteredDest.length"
                                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                    <li v-for="ap in filteredDest" :key="ap.code"
                                        @mousedown="pickDest(ap)"
                                        class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-sm hover:bg-blue-50">
                                        <span class="font-medium text-gray-800">{{ ap.city }}</span>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">{{ ap.code }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Depart date -->
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Depart</label>
                            <div class="relative">
                                <Calendar class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="departDate" type="date" :min="todayStr"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                            </div>
                        </div>

                        <!-- Return date (round-trip only) -->
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Return</label>
                            <div class="relative">
                                <Calendar class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="returnDate" type="date" :min="departDate || todayStr"
                                       :disabled="tripType !== 'round-trip'"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-50 disabled:text-gray-300" />
                            </div>
                        </div>

                        <!-- Search button -->
                        <div class="flex items-end">
                            <button @click="searchFlights"
                                    class="flex h-11 w-full items-center justify-center gap-2 rounded-xl px-6 text-sm font-bold text-white shadow-md transition hover:opacity-90 lg:w-auto"
                                    style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                                <Search class="h-4 w-4" />
                                <span>Search</span>
                            </button>
                        </div>
                    </div>

                    <!-- Cabin / Passengers row -->
                    <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                        <div class="flex items-center gap-2">
                            <Users class="h-4 w-4 text-gray-400" />
                            <label class="text-xs font-medium text-gray-500">Passengers</label>
                            <select v-model="adults" class="rounded-lg border border-gray-200 px-2 py-1 text-sm outline-none focus:border-blue-400">
                                <option v-for="n in 9" :key="n" :value="n">{{ n }} Adult{{ n > 1 ? 's' : '' }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <PlaneTakeoff class="h-4 w-4 text-gray-400" />
                            <label class="text-xs font-medium text-gray-500">Cabin</label>
                            <select v-model="cabinClass" class="rounded-lg border border-gray-200 px-2 py-1 text-sm outline-none focus:border-blue-400">
                                <option value="ECONOMY">Economy</option>
                                <option value="PREMIUM_ECONOMY">Premium Economy</option>
                                <option value="BUSINESS">Business</option>
                                <option value="FIRST">First Class</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-medium text-gray-500">Currency</label>
                            <select v-model="currency" class="rounded-lg border border-gray-200 px-2 py-1 text-sm outline-none focus:border-blue-400">
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                                <option value="NGN">NGN</option>
                                <option value="GHS">GHS</option>
                                <option value="KES">KES</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ── HOTEL SEARCH ── -->
                <div v-if="activeTab === 'hotels'">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Destination</label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="hotelDest" type="text" placeholder="City, hotel or area"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Check-in</label>
                            <div class="relative">
                                <Calendar class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="hotelCheckin" type="date" :min="todayStr"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Check-out</label>
                            <div class="relative">
                                <Calendar class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
                                <input v-model="hotelCheckout" type="date" :min="hotelCheckin || todayStr"
                                       class="w-full rounded-xl border border-gray-200 py-3 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button @click="searchHotels"
                                    class="flex h-11 w-full items-center justify-center gap-2 rounded-xl px-6 text-sm font-bold text-white shadow-md transition hover:opacity-90"
                                    style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                                <Search class="h-4 w-4" /> Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         STATS BAR
    ══════════════════════════════════════════════════ -->
    <section class="border-b border-gray-100 bg-white py-8 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                <div v-for="stat in STATS" :key="stat.label" class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <p class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                            {{ stat.value }}
                        </p>
                        <Star v-if="stat.star" class="h-5 w-5 fill-amber-400 text-amber-400" />
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ stat.label }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         POPULAR DESTINATIONS
    ══════════════════════════════════════════════════ -->
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Section header -->
            <div class="mb-10 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="mb-2 inline-block rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-blue-600">
                        Featured Destinations
                    </span>
                    <h2 class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        Popular Destinations<span class="text-blue-600">.</span>
                    </h2>
                </div>
                <p class="text-sm text-gray-400">Click any city to search flights instantly</p>
            </div>

            <!-- Destination grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div v-for="dest in DESTINATIONS" :key="dest.code"
                     @click="searchRoute('LHR', dest.code)"
                     class="group relative cursor-pointer overflow-hidden rounded-2xl">
                    <img :src="dest.img" :alt="dest.city"
                         class="h-52 w-full object-cover transition duration-500 group-hover:scale-110" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-4">
                        <h3 class="text-base font-bold text-white">{{ dest.city }}</h3>
                        <p class="text-xs text-white/70">{{ dest.country }}</p>
                    </div>
                    <!-- Hover overlay -->
                    <div class="absolute inset-0 flex items-center justify-center bg-blue-600/0 transition duration-300 group-hover:bg-blue-600/20">
                        <span class="translate-y-2 rounded-full bg-white px-4 py-1.5 text-xs font-bold text-blue-600 opacity-0 shadow-lg transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                            Search Flights →
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         WHY CHOOSE OBIALA
    ══════════════════════════════════════════════════ -->
    <section class="bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Section header -->
            <div class="mb-12 text-center">
                <span class="mb-2 inline-block rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-blue-600">
                    Why Obiala
                </span>
                <h2 class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Built for Travellers Who Mean Business<span class="text-blue-600">.</span>
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-gray-500">
                    We cut out the noise — no paid placements, no hidden fees. Just fast, honest flight search.
                </p>
            </div>

            <!-- Feature cards -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="feat in FEATURES" :key="feat.title"
                     class="group rounded-2xl bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-md">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                        <component :is="feat.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mb-2 font-bold text-gray-900">{{ feat.title }}</h3>
                    <p class="text-sm leading-relaxed text-gray-500">{{ feat.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         HOW IT WORKS
    ══════════════════════════════════════════════════ -->
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <span class="mb-2 inline-block rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-blue-600">
                    Simple Process
                </span>
                <h2 class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Book a Flight in 3 Easy Steps<span class="text-blue-600">.</span>
                </h2>
            </div>

            <div class="relative grid gap-8 sm:grid-cols-3">
                <!-- Connecting line (desktop) -->
                <div class="absolute top-8 left-1/6 right-1/6 hidden h-px bg-blue-100 sm:block"></div>

                <div v-for="step in STEPS" :key="step.step" class="relative text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-200">
                        <component :is="step.icon" class="h-6 w-6 text-white" />
                    </div>
                    <span class="mb-1 block text-xs font-bold uppercase tracking-widest text-blue-400">Step {{ step.step }}</span>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">{{ step.title }}</h3>
                    <p class="mx-auto max-w-xs text-sm text-gray-500">{{ step.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         POPULAR ROUTES
    ══════════════════════════════════════════════════ -->
    <section class="bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="mb-2 inline-block rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-blue-600">
                        Top Routes
                    </span>
                    <h2 class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        Popular Flight Routes<span class="text-blue-600">.</span>
                    </h2>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="route in POPULAR_ROUTES" :key="`${route.from}-${route.to}`"
                     @click="searchRoute(route.from, route.to)"
                     class="group flex cursor-pointer items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 transition duration-200 hover:border-blue-300 hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <Plane class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ route.fromCity }} → {{ route.toCity }}</p>
                            <p class="text-xs text-gray-400">{{ route.from }} – {{ route.to }} · Economy</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-extrabold text-blue-600">from ${{ route.price }}</p>
                        <p class="text-xs text-gray-400">one way</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         TESTIMONIALS
    ══════════════════════════════════════════════════ -->
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <span class="mb-2 inline-block rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-blue-600">
                    Testimonials
                </span>
                <h2 class="text-3xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    What Our Travellers Say<span class="text-blue-600">.</span>
                </h2>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div v-for="t in TESTIMONIALS" :key="t.name"
                     class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                    <!-- Stars -->
                    <div class="mb-4 flex gap-1">
                        <Star v-for="i in t.rating" :key="i" class="h-4 w-4 fill-amber-400 text-amber-400" />
                    </div>
                    <p class="mb-6 text-base leading-relaxed text-gray-600">"{{ t.quote }}"</p>
                    <div class="flex items-center gap-3">
                        <img :src="t.img" :alt="t.name"
                             class="h-12 w-12 rounded-full object-cover ring-2 ring-blue-100" />
                        <div>
                            <p class="font-bold text-gray-900">{{ t.name }}</p>
                            <p class="text-xs text-gray-400">{{ t.location }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         CTA
    ══════════════════════════════════════════════════ -->
    <section class="relative overflow-hidden py-20">
        <div class="absolute inset-0" style="background:linear-gradient(135deg,#0a1628,#1c3a6b)"></div>
        <!-- Decorative circles -->
        <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-blue-500/10"></div>
        <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-blue-400/10"></div>

        <div class="relative mx-auto max-w-2xl px-4 text-center sm:px-6">
            <span class="mb-3 inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-blue-300">
                Get Started Today
            </span>
            <h2 class="mb-4 text-3xl font-extrabold text-white sm:text-4xl" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Ready to Explore the World?
            </h2>
            <p class="mb-8 text-base text-blue-200/80">
                Join over 1 million travellers who book smarter with Obiala. Free to sign up — no subscription required.
            </p>
            <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                <a href="/register"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-blue-700 shadow-lg transition hover:bg-blue-50">
                    Create Free Account →
                </a>
                <a href="/flights/search?origin=LHR&destination=DXB&depart=&adults=1&cabin=ECONOMY&currency=USD&trip_type=one-way"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/10">
                    <Search class="h-4 w-4" /> Search Flights
                </a>
            </div>
        </div>
    </section>
</template>
