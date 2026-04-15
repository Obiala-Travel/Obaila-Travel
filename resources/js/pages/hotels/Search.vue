<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

// ── Types ──────────────────────────────────────────────────────────────────
interface Hotel {
    id: string
    name: string
    destination: string
    type: string
    stars: number
    rating: number
    rating_label: string
    price_per_night: number
    total_price: number
    currency: string
    nights: number
    guests: number
    amenities: string[]
    free_cancel: boolean
    emoji: string
    checkin: string
    checkout: string
}

interface Meta {
    total: number
    destination: string
    checkin: string
    checkout: string
    nights: number
    guests: number
    is_mock?: boolean
}

interface Params {
    destination: string
    checkin: string
    checkout: string
    guests: number
    rooms: number
    stars: number
    type: string
    currency: string
}

const props = defineProps<{ hotels: Hotel[]; meta: Meta; params: Params }>();

// ── Sort ───────────────────────────────────────────────────────────────────
const sortBy = ref<'price' | 'rating' | 'stars' | 'name'>('price');

// ── Filters ────────────────────────────────────────────────────────────────
const filterMaxPrice  = ref(0);
const filterAmenities = ref<string[]>([]);
const filterStars     = ref(0);
const showFilters     = ref(false);

const maxPrice = computed(() => Math.max(...props.hotels.map(h => h.price_per_night), 500));

onMounted(() => { filterMaxPrice.value = maxPrice.value; });

const AMENITY_FILTERS = [
    { key: 'Pool',      label: 'Pool' },
    { key: 'WiFi',      label: 'Free WiFi' },
    { key: 'Parking',   label: 'Parking' },
    { key: 'Breakfast', label: 'Breakfast' },
    { key: 'Gym',       label: 'Gym / Fitness' },
    { key: 'Spa',       label: 'Spa' },
];

function toggleAmenity(key: string) {
    const i = filterAmenities.value.indexOf(key);
    if (i >= 0) filterAmenities.value.splice(i, 1);
    else filterAmenities.value.push(key);
}

// ── Filtered + sorted list ─────────────────────────────────────────────────
const filteredHotels = computed(() => {
    let list = [...props.hotels];

    if (filterMaxPrice.value > 0)
        list = list.filter(h => h.price_per_night <= filterMaxPrice.value);

    if (filterStars.value > 0)
        list = list.filter(h => h.stars >= filterStars.value);

    if (filterAmenities.value.length > 0)
        list = list.filter(h =>
            filterAmenities.value.every(key =>
                h.amenities.some(a => a.includes(key))
            )
        );

    return list.sort((a, b) => {
        switch (sortBy.value) {
            case 'price':  return a.price_per_night - b.price_per_night;
            case 'rating': return b.rating - a.rating;
            case 'stars':  return b.stars - a.stars;
            case 'name':   return a.name.localeCompare(b.name);
            default:       return 0;
        }
    });
});

// ── Helpers ────────────────────────────────────────────────────────────────
function formatPrice(price: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency,
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(price);
}

function formatDate(iso: string): string {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

function starArray(n: number): number[] {
    return Array.from({ length: 5 }, (_, i) => i < n ? 1 : 0);
}

const ratingBg: Record<string, string> = {
    Exceptional: 'bg-success',
    Excellent:   'bg-primary',
    'Very Good': 'bg-info',
    Good:        'bg-secondary',
};

const TYPE_LABELS: Record<string, string> = {
    hotel: 'Hotel', apartment: 'Apartment', villa: 'Villa',
    hostel: 'Hostel', resort: 'Resort', bnb: 'B&B',
};

function modifySearch() {
    router.visit('/');
}

function bookHotel(hotel: Hotel) {
    router.visit('/checkout/hotel', { method: 'get', data: { hotel_id: hotel.id } });
}

// ── Labels ─────────────────────────────────────────────────────────────────
const meta = computed(() => props.meta);
const nightsLabel = computed(() =>
    `${meta.value.nights} night${meta.value.nights !== 1 ? 's' : ''}`
);

const amenityIcons: Record<string, string> = {
    'WiFi': 'isax-home-wifi',
    'Pool': 'isax-wind-2',
    'Parking': 'isax-car',
    'Breakfast': 'isax-coffee',
    'Gym': 'isax-weight',
    'Spa': 'isax-scissor',
};
</script>

<template>
    <Head :title="`Hotels in ${params.destination} — Obiala`" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-01 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="breadcrumb-title mb-2">Hotels in {{ params.destination }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item">Hotels</li>
                            <li class="breadcrumb-item active">Search Results</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /Breadcrumb -->

    <!-- Page Wrapper -->
    <div class="content">
        <div class="container">

            <!-- Search summary bar -->
            <div class="card mb-4">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2 rounded-pill bg-light-200 px-3 py-2">
                            <i class="isax isax-building fs-16 text-primary"></i>
                            <span class="fw-semibold fs-14">{{ params.destination }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 rounded-pill border px-3 py-2 fs-14 text-gray-6">
                            <i class="isax isax-calendar fs-14 text-gray-5"></i>
                            {{ formatDate(params.checkin) }} → {{ formatDate(params.checkout) }}
                            <span class="text-muted">· {{ nightsLabel }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 rounded-pill border px-3 py-2 fs-14 text-gray-6">
                            <i class="isax isax-profile-2user fs-14 text-gray-5"></i>
                            {{ params.guests }} guest{{ params.guests > 1 ? 's' : '' }}
                            · {{ params.rooms }} room{{ params.rooms > 1 ? 's' : '' }}
                        </div>
                        <button @click="modifySearch"
                                class="btn btn-outline-primary btn-sm ms-auto d-flex align-items-center gap-2">
                            <i class="isax isax-search-normal fs-14"></i>
                            Modify search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile filter toggle -->
            <div class="d-flex align-items-center justify-content-between mb-3 d-lg-none">
                <p class="fs-14 text-muted mb-0">
                    <span class="fw-semibold text-gray-9">{{ filteredHotels.length }}</span> properties found
                </p>
                <button @click="showFilters = !showFilters"
                        class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="isax isax-setting-5 fs-14"></i>
                    Filters
                </button>
            </div>

            <div class="row">

                <!-- Sidebar -->
                <div :class="['col-xl-3 col-lg-3 theiaStickySidebar', showFilters ? '' : 'd-none d-lg-block']">
                    <div class="card filter-sidebar mb-4 mb-lg-0">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Filters</h5>
                            <a href="#" class="fs-14 link-primary" @click.prevent="filterMaxPrice = maxPrice; filterStars = 0; filterAmenities = []">Reset</a>
                        </div>
                        <div class="card-body p-0">

                            <!-- Price filter -->
                            <div class="accordion accordion-list">
                                <div class="accordion-item border-bottom p-3">
                                    <div class="accordion-header">
                                        <div class="accordion-button p-0" data-bs-toggle="collapse"
                                             data-bs-target="#acc-price" aria-expanded="true" role="button">
                                            <i class="isax isax-coin me-2 text-primary"></i>Price Per Night
                                        </div>
                                    </div>
                                    <div id="acc-price" class="accordion-collapse collapse show">
                                        <div class="accordion-body pt-2">
                                            <input type="range" class="form-range"
                                                   :min="0" :max="maxPrice" step="5"
                                                   v-model.number="filterMaxPrice" />
                                            <div class="filter-range-amount">
                                                <p class="fs-14 mb-0">Max:
                                                    <span class="text-gray-9 fw-medium">
                                                        {{ filterMaxPrice >= maxPrice ? 'Any' : formatPrice(filterMaxPrice, params.currency) }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Star rating filter -->
                                <div class="accordion-item border-bottom p-3">
                                    <div class="accordion-header">
                                        <div class="accordion-button p-0" data-bs-toggle="collapse"
                                             data-bs-target="#acc-stars" aria-expanded="true" role="button">
                                            <i class="isax isax-star me-2 text-primary"></i>Star Rating
                                        </div>
                                    </div>
                                    <div id="acc-stars" class="accordion-collapse collapse show">
                                        <div class="accordion-body pt-2">
                                            <div v-for="s in [{ v: 0, l: 'Any' }, { v: 3, l: '3+ Stars' }, { v: 4, l: '4+ Stars' }, { v: 5, l: '5 Stars only' }]"
                                                 :key="s.v"
                                                 class="form-check d-flex align-items-center ps-0 mb-2">
                                                <input class="form-check-input ms-0 mt-0" type="radio"
                                                       name="stars" :id="`star-${s.v}`"
                                                       :value="s.v" v-model.number="filterStars" />
                                                <label class="form-check-label ms-2" :for="`star-${s.v}`">{{ s.l }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Amenities filter -->
                                <div class="accordion-item p-3">
                                    <div class="accordion-header">
                                        <div class="accordion-button p-0" data-bs-toggle="collapse"
                                             data-bs-target="#acc-amenities" aria-expanded="true" role="button">
                                            <i class="isax isax-candle me-2 text-primary"></i>Amenities
                                        </div>
                                    </div>
                                    <div id="acc-amenities" class="accordion-collapse collapse show">
                                        <div class="accordion-body pt-2">
                                            <div v-for="am in AMENITY_FILTERS" :key="am.key"
                                                 class="form-check d-flex align-items-center ps-0 mb-2">
                                                <input class="form-check-input ms-0 mt-0" type="checkbox"
                                                       :id="`am-${am.key}`"
                                                       :checked="filterAmenities.includes(am.key)"
                                                       @change="toggleAmenity(am.key)" />
                                                <label class="form-check-label ms-2" :for="`am-${am.key}`">{{ am.label }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- /Sidebar -->

                <!-- Results -->
                <div class="col-xl-9 col-lg-9">

                    <!-- Sort bar -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                        <div>
                            <h6 class="mb-1">
                                <span class="text-primary">{{ filteredHotels.length }}</span> properties in {{ params.destination }}
                            </h6>
                            <p class="fs-12 text-muted mb-0">
                                {{ formatDate(params.checkin) }} – {{ formatDate(params.checkout) }}
                                · {{ nightsLabel }} · {{ params.guests }} guest{{ params.guests > 1 ? 's' : '' }}
                                <span v-if="meta.is_mock" class="badge bg-warning text-dark ms-1 fs-10">Demo data</span>
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fs-13 text-muted">Sort by:</span>
                            <select v-model="sortBy" class="form-select form-select-sm" style="width:auto">
                                <option value="price">Price ↑</option>
                                <option value="rating">Rating ↓</option>
                                <option value="stars">Stars ↓</option>
                                <option value="name">Name A–Z</option>
                            </select>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredHotels.length === 0"
                         class="card text-center py-5">
                        <div class="card-body">
                            <div class="fs-1 mb-3">🏨</div>
                            <h5 class="mb-2">No properties found</h5>
                            <p class="text-muted fs-14">Try adjusting your filters or different dates.</p>
                            <button @click="modifySearch" class="btn btn-primary mt-2">New search</button>
                        </div>
                    </div>

                    <!-- Hotel cards -->
                    <div class="hotel-list">
                        <div v-for="hotel in filteredHotels" :key="hotel.id" class="place-item mb-4">

                            <div class="d-flex overflow-hidden rounded-2 border">
                                <!-- Thumbnail -->
                                <div class="position-relative d-flex align-items-center justify-content-center bg-light flex-shrink-0"
                                     style="width:160px;min-height:140px;font-size:3rem">
                                    {{ hotel.emoji }}
                                    <span v-if="hotel.free_cancel"
                                          class="position-absolute top-0 start-0 m-2 badge bg-success fs-10 text-uppercase fw-bold">
                                        Free cancel
                                    </span>
                                </div>

                                <!-- Body -->
                                <div class="place-content flex-1 p-3 pb-2 min-w-0">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap">
                                        <div>
                                            <h5 class="mb-1 text-truncate">{{ hotel.name }}</h5>
                                            <p class="d-flex align-items-center mb-2 fs-14 text-gray-6">
                                                <i class="isax isax-location5 me-2"></i>
                                                {{ hotel.destination }} · {{ TYPE_LABELS[hotel.type] ?? hotel.type }}
                                            </p>
                                        </div>
                                        <!-- Rating badge -->
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span :class="['badge fs-12 fw-bold', ratingBg[hotel.rating_label] ?? 'bg-secondary']">
                                                {{ hotel.rating }}
                                            </span>
                                            <span class="fs-13 text-muted">{{ hotel.rating_label }}</span>
                                        </div>
                                    </div>

                                    <!-- Stars -->
                                    <div class="mb-2">
                                        <span v-for="(filled, si) in starArray(hotel.stars)" :key="si"
                                              :class="filled ? 'text-warning' : 'text-light-200'"
                                              class="fs-14">★</span>
                                    </div>

                                    <!-- Facilities row -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap border-top pt-2 mt-1">
                                        <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                                            <span class="fs-14 fw-medium me-1">Facilities:</span>
                                            <template v-for="(am, ai) in hotel.amenities.slice(0, 4)" :key="ai">
                                                <i :class="['isax me-1 text-primary fs-16', amenityIcons[am] ?? 'isax-tick-circle']"
                                                   :title="am"></i>
                                            </template>
                                            <a v-if="hotel.amenities.length > 4" href="#" class="fs-13 fw-normal text-default">
                                                +{{ hotel.amenities.length - 4 }}
                                            </a>
                                        </div>

                                        <!-- Price + book -->
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div class="text-end">
                                                <h5 class="text-primary mb-0 text-nowrap">
                                                    {{ formatPrice(hotel.price_per_night, hotel.currency) }}
                                                    <span class="fs-14 fw-normal text-default">/ Night</span>
                                                </h5>
                                                <p class="fs-12 text-muted mb-0">
                                                    {{ formatPrice(hotel.total_price, hotel.currency) }} total
                                                </p>
                                            </div>
                                            <button @click="bookHotel(hotel)"
                                                    class="btn btn-primary btn-sm text-nowrap">
                                                Book now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- /Hotel cards -->

                </div>
                <!-- /Results -->

            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->

</template>
