<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
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
const DESTINATIONS = [
    { city: 'Dubai',     country: 'UAE',          code: 'DXB', img: '/images/destination/destination-01.jpg' },
    { city: 'London',    country: 'United Kingdom', code: 'LHR', img: '/images/destination/destination-02.jpg' },
    { city: 'New York',  country: 'United States', code: 'JFK', img: '/images/destination/destination-03.jpg' },
    { city: 'Paris',     country: 'France',        code: 'CDG', img: '/images/destination/destination-04.jpg' },
    { city: 'Singapore', country: 'Singapore',     code: 'SIN', img: '/images/destination/destination-05.jpg' },
    { city: 'Lagos',     country: 'Nigeria',       code: 'LOS', img: '/images/destination/destination-06.jpg' },
];

const POPULAR_ROUTES = [
    { from: 'LHR', fromCity: 'London',       to: 'DXB', toCity: 'Dubai',     price: 320, img: '/images/flight/flight-01.jpg' },
    { from: 'LHR', fromCity: 'London',       to: 'JFK', toCity: 'New York',  price: 410, img: '/images/flight/flight-02.jpg' },
    { from: 'LOS', fromCity: 'Lagos',        to: 'LHR', toCity: 'London',    price: 580, img: '/images/flight/flight-03.jpg' },
    { from: 'NBO', fromCity: 'Nairobi',      to: 'DXB', toCity: 'Dubai',     price: 290, img: '/images/flight/flight-04.jpg' },
    { from: 'ACC', fromCity: 'Accra',        to: 'CDG', toCity: 'Paris',     price: 490, img: '/images/flight/flight-05.jpg' },
    { from: 'JNB', fromCity: 'Johannesburg', to: 'LHR', toCity: 'London',    price: 620, img: '/images/flight/flight-06.jpg' },
];

const TESTIMONIALS = [
    {
        quote: "Obiala made booking our Lagos to London trip effortless. The prices were better than anything I found elsewhere, and the e-ticket arrived instantly.",
        name: 'Amara O.',
        location: 'Lagos, Nigeria',
        rating: 5,
        img: '/images/users/user-01.jpg',
        headline: 'Smooth Booking Experience!',
    },
    {
        quote: "I've used a lot of booking platforms, but Obiala is by far the cleanest experience. Search is fast, checkout is simple, and I love the price alerts.",
        name: 'James K.',
        location: 'Nairobi, Kenya',
        rating: 5,
        img: '/images/users/user-28.jpg',
        headline: 'Excellent Customer Service',
    },
    {
        quote: "Found a brilliant deal from Accra to Paris — saved over $120 compared to other sites. The whole process from search to e-ticket took less than 10 minutes.",
        name: 'Kwame A.',
        location: 'Accra, Ghana',
        rating: 5,
        img: '/images/users/user-22.jpg',
        headline: 'Great Value & Fast Checkout',
    },
    {
        quote: "Received my confirmation email almost immediately, and all the flight details were accurate. Will definitely use Obiala again for my next trip.",
        name: 'Sarah M.',
        location: 'London, United Kingdom',
        rating: 5,
        img: '/images/users/user-27.jpg',
        headline: 'Instant Confirmation!',
    },
];

const FAQS = [
    { id: 'one',   q: 'How do I search for flights?',                     a: 'Enter your departure city, destination, travel dates and number of passengers, then click Search. We display live fares from 500+ airlines instantly.' },
    { id: 'two',   q: 'Are the prices shown final with no hidden fees?',   a: 'Yes. The price you see at checkout is the full price — taxes and carrier fees included. We never add surprise charges at the last step.' },
    { id: 'three', q: 'How quickly will I receive my e-ticket?',           a: 'Your e-ticket is emailed to you immediately after payment confirmation, usually within seconds.' },
    { id: 'four',  q: 'Can I book for multiple passengers?',               a: 'Yes. You can add up to 9 adults per booking. Select the number of passengers in the search form before searching.' },
    { id: 'five',  q: 'What is the cancellation or refund policy?',        a: 'Cancellation policies vary by airline and fare type. The specific conditions are displayed on the fare selection screen before you pay.' },
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

function cabinLabel(val: string) {
    return val.charAt(0) + val.slice(1).toLowerCase().replace('_', ' ');
}
</script>

<template>
    <Head title="Obiala Travel — Search & Book Flights" />

    <!-- Hero Section -->
    <section class="hero-section-four">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-10 col-md-12 mx-auto">
                        <div class="banner-content text-center mx-auto">
                            <h1 class="text-white display-4 mb-2">
                                Discover the World, One
                                <span class="flight-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                Flight at a Time with Obiala!
                            </h1>
                            <p class="text-white mx-auto">
                                Search live fares from 500+ airlines. Book in minutes. Fly with confidence.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Hero Section -->

    <!-- Banner Search -->
    <section class="banner-search-four">
        <div class="container">
            <div class="banner-form card mb-0">
                <div class="card-body">

                    <!-- Tabs (Flights / Hotels) -->
                    <ul class="nav nav-tabs nav-tabs-solid mb-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center gap-1"
                                    :class="{ active: activeTab === 'flights' }"
                                    @click="activeTab = 'flights'">
                                <i class="isax isax-airplane me-1"></i>Flights
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center gap-1"
                                    :class="{ active: activeTab === 'hotels' }"
                                    @click="activeTab = 'hotels'">
                                <i class="isax isax-building-3 me-1"></i>Hotels
                            </button>
                        </li>
                    </ul>

                    <!-- ── FLIGHT SEARCH ── -->
                    <div v-if="activeTab === 'flights'">
                        <!-- Trip type -->
                        <div class="d-flex align-items-center flex-wrap mb-3">
                            <div class="form-check d-flex align-items-center me-3 mb-1">
                                <input class="form-check-input mt-0" type="radio" v-model="tripType"
                                       value="one-way" id="oneway">
                                <label class="form-check-label fs-14 ms-2" for="oneway">One Way</label>
                            </div>
                            <div class="form-check d-flex align-items-center me-3 mb-1">
                                <input class="form-check-input mt-0" type="radio" v-model="tripType"
                                       value="round-trip" id="roundtrip">
                                <label class="form-check-label fs-14 ms-2" for="roundtrip">Round Trip</label>
                            </div>
                            <div class="form-check d-flex align-items-center mb-1">
                                <input class="form-check-input mt-0" type="radio" v-model="tripType"
                                       value="multi-city" id="multicity">
                                <label class="form-check-label fs-14 ms-2" for="multicity">Multi-City</label>
                            </div>
                        </div>

                        <!-- Search error -->
                        <div v-if="searchError" class="alert alert-danger py-2 mb-3 fs-14">
                            {{ searchError }}
                        </div>

                        <!-- Form row -->
                        <div class="d-lg-flex">
                        <div class="d-flex form-info">

                            <!-- From -->
                            <div class="form-item position-relative">
                                <label class="form-label fs-14 text-default mb-1">From</label>
                                <input v-model="originQuery" type="text" class="form-control"
                                       placeholder="City or airport"
                                       @input="showOriginDrop = true; origin = ''"
                                       @focus="showOriginDrop = originQuery.length > 0"
                                       @blur="setTimeout(() => showOriginDrop = false, 150)">
                                <p class="fs-12 mb-0 text-muted">
                                    {{ origin ? (AIRPORTS.find(a => a.code === origin)?.name ?? '') : 'Enter departure city' }}
                                </p>
                                <ul v-if="showOriginDrop && filteredOrigin.length"
                                    class="dropdown-menu show w-100" style="z-index:1050">
                                    <li v-for="ap in filteredOrigin" :key="ap.code"
                                        class="border-bottom" @mousedown="pickOrigin(ap)">
                                        <a class="dropdown-item" href="#">
                                            <h6 class="fs-15 fw-medium mb-0">{{ ap.city }}</h6>
                                            <p class="mb-0 fs-12 text-muted">{{ ap.name }} ({{ ap.code }})</p>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- To (swap icon is a way-icon badge) -->
                            <div class="form-item position-relative ps-2 ps-sm-3">
                                <label class="form-label fs-14 text-default mb-1">To</label>
                                <input v-model="destQuery" type="text" class="form-control"
                                       placeholder="City or airport"
                                       @input="showDestDrop = true; destination = ''"
                                       @focus="showDestDrop = destQuery.length > 0"
                                       @blur="setTimeout(() => showDestDrop = false, 150)">
                                <p class="fs-12 mb-0 text-muted">
                                    {{ destination ? (AIRPORTS.find(a => a.code === destination)?.name ?? '') : 'Enter destination city' }}
                                </p>
                                <button type="button" @click="swapAirports"
                                        class="way-icon badge badge-primary rounded-pill translate-middle border-0"
                                        title="Swap airports">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <ul v-if="showDestDrop && filteredDest.length"
                                    class="dropdown-menu show w-100" style="z-index:1050">
                                    <li v-for="ap in filteredDest" :key="ap.code"
                                        class="border-bottom" @mousedown="pickDest(ap)">
                                        <a class="dropdown-item" href="#">
                                            <h6 class="fs-15 fw-medium mb-0">{{ ap.city }}</h6>
                                            <p class="mb-0 fs-12 text-muted">{{ ap.name }} ({{ ap.code }})</p>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Departure -->
                            <div class="form-item">
                                <label class="form-label fs-14 text-default mb-1">Departure</label>
                                <input v-model="departDate" type="date" :min="todayStr" class="form-control">
                                <p class="fs-12 mb-0 text-muted">Select date</p>
                            </div>

                            <!-- Return -->
                            <div class="form-item">
                                <label class="form-label fs-14 text-default mb-1">Return</label>
                                <input v-model="returnDate" type="date"
                                       :min="departDate || todayStr"
                                       :disabled="tripType !== 'round-trip'"
                                       class="form-control">
                                <p class="fs-12 mb-0 text-muted">Select date</p>
                            </div>

                            <!-- Travellers & Cabin dropdown -->
                            <div class="form-item dropdown">
                                <div data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                     role="menu" style="cursor:pointer">
                                    <label class="form-label fs-14 text-default mb-1">Travellers &amp; Cabin</label>
                                    <h5 class="mb-0">
                                        {{ adults }}
                                        <span class="fw-normal fs-14">{{ adults === 1 ? 'Person' : 'Persons' }}</span>
                                    </h5>
                                    <p class="fs-12 mb-0 text-muted">
                                        {{ adults }} Adult{{ adults > 1 ? 's' : '' }}, {{ cabinLabel(cabinClass) }}
                                    </p>
                                </div>
                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px">
                                    <h6 class="mb-3">Travellers &amp; Class</h6>
                                    <div class="mb-3">
                                        <label class="form-label text-default mb-2">Adults</label>
                                        <select v-model="adults" class="form-select form-select-sm">
                                            <option v-for="n in 9" :key="n" :value="n">
                                                {{ n }} Adult{{ n > 1 ? 's' : '' }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-default mb-2">Cabin Class</label>
                                        <select v-model="cabinClass" class="form-select form-select-sm">
                                            <option value="ECONOMY">Economy</option>
                                            <option value="PREMIUM_ECONOMY">Premium Economy</option>
                                            <option value="BUSINESS">Business</option>
                                            <option value="FIRST">First Class</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label text-default mb-2">Currency</label>
                                        <select v-model="currency" class="form-select form-select-sm">
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

                        </div><!-- /form-info -->
                            <!-- Search button -->
                            <div class="ms-lg-3 mt-3 mt-lg-0">
                                <button @click="searchFlights" type="button"
                                        class="btn btn-primary btn-lg d-flex align-items-center h-100">
                                    <i class="isax isax-search-normal me-2"></i>Search
                                </button>
                            </div>
                        </div><!-- /d-lg-flex -->
                    </div>
                    <!-- /Flight search -->

                    <!-- ── HOTEL SEARCH ── -->
                    <div v-if="activeTab === 'hotels'">
                        <div class="d-lg-flex">
                        <div class="d-flex form-info">

                            <div class="form-item flex-fill">
                                <label class="form-label fs-14 text-default mb-1">Destination</label>
                                <input v-model="hotelDest" type="text" class="form-control"
                                       placeholder="City, hotel or area">
                                <p class="fs-12 mb-0 text-muted">Where are you going?</p>
                            </div>

                            <div class="form-item">
                                <label class="form-label fs-14 text-default mb-1">Check-in</label>
                                <input v-model="hotelCheckin" type="date" :min="todayStr" class="form-control">
                                <p class="fs-12 mb-0 text-muted">Select date</p>
                            </div>

                            <div class="form-item">
                                <label class="form-label fs-14 text-default mb-1">Check-out</label>
                                <input v-model="hotelCheckout" type="date"
                                       :min="hotelCheckin || todayStr" class="form-control">
                                <p class="fs-12 mb-0 text-muted">Select date</p>
                            </div>

                            <div class="form-item dropdown">
                                <div data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                     role="menu" style="cursor:pointer">
                                    <label class="form-label fs-14 text-default mb-1">Guests</label>
                                    <h5 class="mb-0">
                                        {{ hotelGuests }}
                                        <span class="fw-normal fs-14">{{ hotelGuests === 1 ? 'Guest' : 'Guests' }}</span>
                                    </h5>
                                    <p class="fs-12 mb-0 text-muted">1 Room</p>
                                </div>
                                <div class="dropdown-menu p-3" style="min-width:220px">
                                    <label class="form-label text-default mb-2">Guests</label>
                                    <select v-model="hotelGuests" class="form-select form-select-sm">
                                        <option v-for="n in 10" :key="n" :value="n">
                                            {{ n }} Guest{{ n > 1 ? 's' : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                        </div><!-- /form-info -->
                            <div class="ms-lg-3 mt-3 mt-lg-0">
                                <button @click="searchHotels" type="button"
                                        class="btn btn-primary btn-lg d-flex align-items-center h-100">
                                    <i class="isax isax-search-normal me-2"></i>Search
                                </button>
                            </div>
                        </div><!-- /d-lg-flex -->
                    </div>
                    <!-- /Hotel search -->

                </div>
            </div>
        </div>
    </section>
    <!-- /Banner Search -->

    <!-- Destination Section -->
    <section class="section destination-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-10 text-center">
                    <div class="section-header section-header-four text-center">
                        <h2 class="mb-2"><span>Popular</span> Locations</h2>
                        <p class="sub-title">
                            Top destinations our travellers love — click any city to search flights instantly.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center g-4">
                <div v-for="dest in DESTINATIONS" :key="dest.code"
                     class="col-lg-4 col-sm-6"
                     @click="searchRoute('LHR', dest.code)"
                     style="cursor:pointer">
                    <div class="location-wrap">
                        <img :src="dest.img" :alt="dest.city">
                        <span class="loc-name bg-white">{{ dest.city }}</span>
                        <a @click.prevent="searchRoute('LHR', dest.code)"
                           href="#" class="loc-view">
                            <i class="isax isax-arrow-right-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Destination Section -->

    <!-- Place Section (Popular Routes) -->
    <section class="section place-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-10 text-center">
                    <div class="section-header section-header-four mb-4 text-center">
                        <h2 class="mb-2"><span>Popular</span> Flight Routes.</h2>
                        <p class="sub-title">
                            Live-priced routes our travellers book most. Click any card to search instantly.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div v-for="route in POPULAR_ROUTES" :key="`${route.from}-${route.to}`"
                     class="col-lg-4 col-md-6">
                    <div class="place-item mb-0" style="cursor:pointer"
                         @click="searchRoute(route.from, route.to)">
                        <div class="place-img">
                            <img :src="route.img" class="img-fluid" :alt="`${route.fromCity} to ${route.toCity}`">
                            <div class="fav-item">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-indigo">Popular</span>
                                </div>
                                <span class="badge badge-warning badge-xs text-gray-9 fs-13 fw-medium rounded">
                                    <i class="ti ti-star-filled me-1"></i>4.8
                                </span>
                            </div>
                        </div>
                        <div class="place-content">
                            <div class="flight-loc d-flex align-items-center justify-content-between mb-2">
                                <span class="loc-name d-inline-flex align-items-center">
                                    <i class="isax isax-airplane rotate-45 me-2"></i>{{ route.fromCity }}
                                </span>
                                <span class="arrow-icon"><i class="isax isax-arrow-2"></i></span>
                                <span class="loc-name d-inline-flex align-items-center">
                                    <i class="isax isax-airplane rotate-135 me-2"></i>{{ route.toCity }}
                                </span>
                            </div>
                            <h5 class="text-truncate mb-1">
                                {{ route.from }} – {{ route.to }} · Economy
                            </h5>
                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                <h6 class="text-primary mb-0">
                                    <span class="fs-14 fw-normal text-default">From </span>
                                    ${{ route.price }}
                                </h6>
                                <span class="badge bg-outline-success fs-10 fw-medium">One Way</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Place Section -->

    <!-- About Section -->
    <section class="section about-section-four bg-light-200">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="section-header section-header-four mb-4">
                        <h2 class="mb-2">
                            <span>Where speed</span> meets simplicity — booking flights made effortless.
                        </h2>
                        <p class="sub-title">
                            Our mission is to give every traveller access to the best fares in seconds.
                            No hidden fees, no confusion — just fast, honest flight search powered by live airline data.
                        </p>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <h6 class="display-6">500+</h6>
                                <p>Airlines Worldwide</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <h6 class="display-6">200+</h6>
                                <p>Destinations Covered</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <h6 class="display-6">1M+</h6>
                                <p>Happy Travellers</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <h6 class="display-6">4.8<span class="fs-14 fw-normal">/5.0</span></h6>
                                <p>Average Rating</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-3 mb-0 mb-md-4 mb-lg-0">
                        <a href="/register"
                           class="btn btn-dark d-inline-flex align-items-center">
                            <i class="isax isax-add-circle5 me-2"></i>Create Free Account
                        </a>
                        <a href="/flights/search"
                           class="btn btn-primary d-inline-flex align-items-center">
                            <i class="isax isax-search-normal me-2"></i>Search Flights
                        </a>
                    </div>
                </div>
                <div class="col-lg-7 d-flex ps-lg-0">
                    <div class="flight-about d-lg-flex align-items-center flex-fill">
                        <img src="/images/about/about-flights.svg" alt="About Obiala Travel">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /About Section -->

    <!-- Support Ticker Section -->
    <section class="support-section bg-dark support-section-five">
        <div class="horizontal-slide d-flex" data-direction="left" data-speed="slow">
            <div class="slide-list d-flex">
                <div class="support-item"><h5>Personalized Itineraries</h5></div>
                <div class="support-item"><h5>500+ Airlines</h5></div>
                <div class="support-item"><h5>Instant E-Tickets</h5></div>
                <div class="support-item"><h5>Secure Checkout</h5></div>
                <div class="support-item"><h5>Price Alerts</h5></div>
                <div class="support-item"><h5>24/7 Support</h5></div>
                <div class="support-item"><h5>Multiple Currencies</h5></div>
                <div class="support-item"><h5>Live Fare Data</h5></div>
            </div>
        </div>
    </section>
    <!-- /Support Ticker -->

    <!-- Testimonial Section -->
    <section class="section testimonial-section z-1 bg-light-200">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <div class="flex-fill position-relative">
                        <div class="mb-4 mb-lg-0 success-wrap">
                            <div class="section-header section-header-four">
                                <h2 class="mb-2"><span>Success</span> stories in their own words</h2>
                                <p class="sub-title">
                                    Read what our satisfied travellers have to say about booking with Obiala.
                                </p>
                            </div>
                            <h6 class="fw-medium mb-1">Trusted by 1M+ customers</h6>
                            <div class="d-flex align-items-center fs-14">
                                <i class="ti ti-star-filled text-primary me-1"></i>
                                <i class="ti ti-star-filled text-primary me-1"></i>
                                <i class="ti ti-star-filled text-primary me-1"></i>
                                <i class="ti ti-star-filled text-primary me-1"></i>
                                <i class="ti ti-star-filled text-primary me-2"></i>
                                <p class="fs-14">
                                    <span class="text-gray-9">4.8/5.0</span> (From 12,400+ Reviews)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="testimonial-success">
                        <div class="row g-4">
                            <div v-for="t in TESTIMONIALS" :key="t.name" class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <a href="#" class="avatar avatar-lg flex-shrink-0">
                                                <img :src="t.img" class="rounded-circle" :alt="t.name">
                                            </a>
                                            <div class="ms-2">
                                                <h6 class="fs-16 fw-medium">{{ t.name }}</h6>
                                                <p>{{ t.location }}</p>
                                            </div>
                                        </div>
                                        <h6 class="mb-2">{{ t.headline }}</h6>
                                        <p class="mb-2">{{ t.quote }}</p>
                                        <div class="d-flex align-items-center">
                                            <i v-for="i in t.rating" :key="i"
                                               class="ti ti-star-filled text-primary me-1"></i>
                                            <p class="mb-0">{{ t.rating }}.0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Testimonial Section -->

    <!-- FAQ Section -->
    <section class="faq-section-four section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-12 text-center">
                    <div class="section-header section-header-four text-center">
                        <h2 class="mb-2"><span>Frequently</span> Asked Questions</h2>
                        <p class="sub-title">
                            Everything you need to know about booking flights with Obiala.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion accordion-flush faq-four" id="accordionFaq">
                        <div v-for="(faq, idx) in FAQS" :key="faq.id"
                             :class="['accordion-item mb-2', idx === 0 ? 'show' : '']">
                            <h2 class="accordion-header">
                                <button class="accordion-button" :class="{ collapsed: idx !== 0 }"
                                        type="button" data-bs-toggle="collapse"
                                        :data-bs-target="`#faq-${faq.id}`"
                                        :aria-expanded="idx === 0 ? 'true' : 'false'">
                                    {{ faq.q }}
                                </button>
                            </h2>
                            <div :id="`faq-${faq.id}`"
                                 class="accordion-collapse collapse"
                                 :class="{ show: idx === 0 }"
                                 data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    <p class="mb-0">{{ faq.a }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA inside FAQ -->
                    <div class="business-wrap bg-dark mt-4">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="business-info">
                                    <h2 class="display-6 text-white mb-3">
                                        Ready to find your next flight?
                                    </h2>
                                    <p class="text-light mb-4">
                                        Join over 1 million travellers who book smarter with Obiala.
                                        Free to sign up — no subscription required.
                                    </p>
                                    <a href="/register"
                                       class="btn btn-primary d-inline-flex align-items-center">
                                        <i class="isax isax-add-circle me-2"></i>Create Free Account
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-5 d-flex align-items-center justify-content-end">
                                <div class="business-img">
                                    <img src="/images/about/about-flights.svg" alt="img">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- /FAQ Section -->

</template>
