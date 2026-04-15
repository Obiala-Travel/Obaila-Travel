<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

// ── Types ──────────────────────────────────────────────────────────────────
interface Baggage { carry_on: boolean; checked: boolean }

interface Offer {
    id: string
    price: number
    currency: string
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
    baggages: Baggage
    expires_at: string
    raw: Record<string, unknown>
}

interface Meta {
    offer_request_id: string | null
    total: number
    is_mock?: boolean
}

interface Params {
    origin: string
    destination: string
    depart: string
    return_date?: string
    adults: number
    cabin: string
    currency: string
    trip_type: string
}

const props = defineProps<{ offers: Offer[]; meta: Meta; params: Params }>();

// ── Sort ───────────────────────────────────────────────────────────────────
const sortBy = ref<'price' | 'duration' | 'departure' | 'arrival'>('price');

// ── Filters ────────────────────────────────────────────────────────────────
const filterStops    = ref<number | null>(null);
const filterAirlines = ref<string[]>([]);
const filterMaxPrice = ref(0);
const showFilters    = ref(false);

const allAirlines = computed(() => {
    const seen = new Set<string>();
    return props.offers
        .filter(o => { const k = o.airline_iata; if (seen.has(k)) return false; seen.add(k); return true; })
        .map(o => ({ iata: o.airline_iata, name: o.airline_name }));
});

const maxPrice = computed(() => Math.max(...props.offers.map(o => o.price), 500));

onMounted(() => {
    filterMaxPrice.value = maxPrice.value;
});

function toggleAirline(iata: string) {
    const i = filterAirlines.value.indexOf(iata);
    if (i >= 0) filterAirlines.value.splice(i, 1);
    else filterAirlines.value.push(iata);
}

// ── Computed result list ───────────────────────────────────────────────────
const filteredOffers = computed(() => {
    let list = [...props.offers];

    if (filterStops.value !== null)
        list = list.filter(o => o.stops === filterStops.value);

    if (filterAirlines.value.length > 0)
        list = list.filter(o => filterAirlines.value.includes(o.airline_iata));

    if (filterMaxPrice.value > 0)
        list = list.filter(o => o.price <= filterMaxPrice.value);

    return list.sort((a, b) => {
        switch (sortBy.value) {
            case 'price':     return a.price - b.price;
            case 'duration':  return parseDuration(a.duration) - parseDuration(b.duration);
            case 'departure': return a.departs_at.localeCompare(b.departs_at);
            case 'arrival':   return a.arrives_at.localeCompare(b.arrives_at);
            default:          return 0;
        }
    });
});

// ── Helpers ────────────────────────────────────────────────────────────────
function parseDuration(iso: string): number {
    const m = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?/);
    if (!m) return 0;
    return (parseInt(m[1] ?? '0') * 60) + parseInt(m[2] ?? '0');
}

function formatDuration(iso: string): string {
    const mins = parseDuration(iso);
    if (!mins) return iso;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h ${m > 0 ? m + 'm' : ''}`.trim() : `${m}m`;
}

function formatTime(iso: string): string {
    if (!iso) return '--:--';
    try {
        return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
    } catch { return '--:--'; }
}

function formatDate(iso: string): string {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
    } catch { return ''; }
}

function formatPrice(price: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency,
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(price);
}

function airlineLogoUrl(iata: string): string {
    return `https://pics.avs.io/60/60/${iata}.png`;
}

const cabinLabels: Record<string, string> = {
    economy: 'Economy', premium_economy: 'Premium Economy',
    business: 'Business', first: 'First Class',
    ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Premium Economy',
    BUSINESS: 'Business', FIRST: 'First Class',
};

// ── Saved flights ──────────────────────────────────────────────────────────
const savedIds = ref<Set<string>>(new Set());
function isSaved(id: string) { return savedIds.value.has(id); }
function toggleSave(offer: Offer) {
    if (savedIds.value.has(offer.id)) savedIds.value.delete(offer.id);
    else savedIds.value.add(offer.id);
}

// ── Price alert sidebar ────────────────────────────────────────────────────
const alertEmail  = ref('');
const alertSet    = ref(false);
function setPriceAlert() {
    if (!alertEmail.value) return;
    alertSet.value = true;
    setTimeout(() => { alertSet.value = false; alertEmail.value = ''; }, 3000);
}

// ── Book / modify ──────────────────────────────────────────────────────────
function bookFlight(offer: Offer) {
    router.post('/checkout/flight/select', { offer });
}
function modifySearch() {
    router.visit('/');
}

// ── Formatted header ───────────────────────────────────────────────────────
const routeLabel  = computed(() => `${props.params.origin} → ${props.params.destination}`);
const departsLabel = computed(() => {
    if (!props.params.depart) return '';
    return new Date(props.params.depart).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
});
const tripLabel = computed(() => {
    if (props.params.trip_type === 'round-trip' && props.params.return_date) {
        const ret = new Date(props.params.return_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        return `Return ${ret}`;
    }
    return 'One way';
});
</script>

<template>
    <Head :title="`${params.origin} → ${params.destination} Flights — Obiala`" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1 fs-14 py-2 px-3">
                        <i class="isax isax-airplane rotate-45 me-1"></i>
                        {{ params.origin }} → {{ params.destination }}
                    </span>
                    <span class="badge bg-white text-gray-9 border d-inline-flex align-items-center gap-1 fs-12 py-2 px-3">
                        <i class="isax isax-calendar-2 me-1"></i>
                        {{ departsLabel }}
                        <template v-if="params.trip_type === 'round-trip' && params.return_date">
                            · {{ tripLabel }}
                        </template>
                    </span>
                    <span class="badge bg-white text-gray-9 border d-inline-flex align-items-center gap-1 fs-12 py-2 px-3">
                        <i class="isax isax-people me-1"></i>
                        {{ params.adults }} adult{{ params.adults > 1 ? 's' : '' }}
                        · {{ cabinLabels[params.cabin] ?? params.cabin }}
                    </span>
                    <span v-if="meta.is_mock"
                          class="badge bg-warning text-dark fs-12 py-2 px-3">
                        Demo data
                    </span>
                </div>
                <button @click="modifySearch"
                        class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                    <i class="isax isax-search-normal me-1"></i>Modify Search
                </button>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="content">
        <div class="container">

            <!-- Mobile filter toggle -->
            <div class="d-flex d-lg-none align-items-center justify-content-between mb-3">
                <p class="fs-14 mb-0 text-gray-9">
                    <strong>{{ filteredOffers.length }}</strong> flights found
                </p>
                <button @click="showFilters = !showFilters"
                        class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                    <i class="isax isax-setting-4 me-1"></i>
                    Filters
                    <span v-if="filterStops !== null || filterAirlines.length > 0"
                          class="badge bg-primary rounded-pill ms-1">
                        {{ (filterStops !== null ? 1 : 0) + filterAirlines.length }}
                    </span>
                </button>
            </div>

            <div class="row g-4">

                <!-- ── Sidebar ──────────────────────────────────────────────── -->
                <div :class="['col-lg-3', showFilters ? 'd-block' : 'd-none d-lg-block']">
                    <div class="theiaStickySidebar">

                        <!-- Price Alert -->
                        <div class="card filter-sidebar mb-4">
                            <div class="card-header d-flex align-items-center justify-content-between bg-primary">
                                <h6 class="text-white mb-0 d-flex align-items-center gap-2">
                                    <i class="isax isax-notification-bing"></i>Price Alert
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="fs-14 text-muted mb-3">
                                    Get notified when fares drop on this route.
                                </p>
                                <template v-if="!alertSet">
                                    <input v-model="alertEmail" type="email"
                                           class="form-control form-control-sm mb-2"
                                           placeholder="your@email.com" />
                                    <button @click="setPriceAlert"
                                            class="btn btn-primary btn-sm w-100">
                                        Set Alert
                                    </button>
                                </template>
                                <div v-else class="alert alert-success py-2 mb-0 fs-14">
                                    <i class="isax isax-tick-circle me-2"></i>Alert set! We'll email you.
                                </div>
                            </div>
                        </div>

                        <!-- Filters card -->
                        <div class="card filter-sidebar mb-4">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="mb-0">Filters</h5>
                                <a href="#" class="fs-14 link-primary"
                                   @click.prevent="filterStops = null; filterAirlines = []; filterMaxPrice = maxPrice">
                                    Reset
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="accordion accordion-list">

                                    <!-- Stops -->
                                    <div class="accordion-item border-bottom p-3">
                                        <div class="accordion-header">
                                            <div class="accordion-button p-0 fw-medium"
                                                 data-bs-toggle="collapse"
                                                 data-bs-target="#filter-stops"
                                                 aria-expanded="true" role="button">
                                                <i class="isax isax-airplane4 me-2 text-primary"></i>Stops
                                            </div>
                                        </div>
                                        <div id="filter-stops" class="accordion-collapse collapse show">
                                            <div class="accordion-body pt-2">
                                                <div v-for="opt in [
                                                    { value: null, label: 'Any stops' },
                                                    { value: 0,    label: 'Direct only' },
                                                    { value: 1,    label: '1 stop max' },
                                                    { value: 2,    label: '2+ stops' }
                                                ]" :key="String(opt.value)"
                                                     class="form-check d-flex align-items-center ps-0 mb-2">
                                                    <input class="form-check-input ms-0 mt-0" type="radio"
                                                           name="filterStops"
                                                           :value="opt.value" v-model="filterStops">
                                                    <label class="form-check-label ms-2">{{ opt.label }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Airlines -->
                                    <div v-if="allAirlines.length > 1" class="accordion-item border-bottom p-3">
                                        <div class="accordion-header">
                                            <div class="accordion-button p-0 fw-medium"
                                                 data-bs-toggle="collapse"
                                                 data-bs-target="#filter-airlines"
                                                 aria-expanded="true" role="button">
                                                <i class="isax isax-buildings-2 me-2 text-primary"></i>Airlines
                                            </div>
                                        </div>
                                        <div id="filter-airlines" class="accordion-collapse collapse show">
                                            <div class="accordion-body pt-2">
                                                <div v-for="al in allAirlines" :key="al.iata"
                                                     class="form-check d-flex align-items-center ps-0 mb-2">
                                                    <input class="form-check-input ms-0 mt-0" type="checkbox"
                                                           :id="`al-${al.iata}`"
                                                           :checked="filterAirlines.includes(al.iata)"
                                                           @change="toggleAirline(al.iata)">
                                                    <label class="form-check-label ms-2 d-flex align-items-center gap-2"
                                                           :for="`al-${al.iata}`">
                                                        <img :src="airlineLogoUrl(al.iata)" :alt="al.name"
                                                             class="rounded" style="height:18px;width:auto"
                                                             @error="($event.target as HTMLImageElement).style.display='none'" />
                                                        {{ al.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Max price -->
                                    <div class="accordion-item p-3">
                                        <div class="accordion-header">
                                            <div class="accordion-button p-0 fw-medium"
                                                 data-bs-toggle="collapse"
                                                 data-bs-target="#filter-price"
                                                 aria-expanded="true" role="button">
                                                <i class="isax isax-coin me-2 text-primary"></i>Max Price
                                            </div>
                                        </div>
                                        <div id="filter-price" class="accordion-collapse collapse show">
                                            <div class="accordion-body pt-2">
                                                <input type="range" class="form-range"
                                                       :min="0" :max="maxPrice" step="10"
                                                       v-model.number="filterMaxPrice" />
                                                <div class="d-flex justify-content-between fs-12 text-muted mt-1">
                                                    <span>{{ params.currency }} 0</span>
                                                    <span class="fw-medium text-primary">
                                                        {{ formatPrice(filterMaxPrice, params.currency) }}
                                                    </span>
                                                    <span>{{ formatPrice(maxPrice, params.currency) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- /Sidebar -->

                <!-- ── Results area ─────────────────────────────────────────── -->
                <div class="col-lg-9">

                    <!-- Results header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                        <h6 class="mb-0">
                            <span class="text-primary">{{ filteredOffers.length }}</span>
                            flight{{ filteredOffers.length !== 1 ? 's' : '' }} found
                            <span class="fw-normal text-muted fs-14">
                                — {{ routeLabel }} · {{ departsLabel }} · {{ tripLabel }}
                            </span>
                        </h6>
                        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                            <span class="fs-14 text-muted">Sort:</span>
                            <select v-model="sortBy"
                                    class="form-select form-select-sm" style="width:auto">
                                <option value="price">Cheapest first</option>
                                <option value="duration">Shortest flight</option>
                                <option value="departure">Earliest departure</option>
                                <option value="arrival">Earliest arrival</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sign-in nudge -->
                    <div class="bg-info br-10 p-3 pb-2 mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <p class="fs-14 fw-medium mb-2 d-inline-flex align-items-center">
                                <i class="isax isax-info-circle me-2"></i>
                                Sign in to save flights and get fare alerts sent to your inbox.
                            </p>
                            <a href="/login" class="btn btn-white btn-sm mb-2">Sign In</a>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredOffers.length === 0"
                         class="card text-center py-5">
                        <div class="card-body">
                            <div class="avatar avatar-xl mx-auto mb-3 bg-light rounded-circle d-flex align-items-center justify-content-center">
                                <i class="isax isax-airplane4 fs-1 text-muted"></i>
                            </div>
                            <h5>No flights match your filters</h5>
                            <p class="text-muted fs-14">Try adjusting stops, airlines, or your price limit.</p>
                            <button @click="modifySearch"
                                    class="btn btn-primary mt-2">
                                <i class="isax isax-search-normal me-2"></i>New Search
                            </button>
                        </div>
                    </div>

                    <!-- Flight cards -->
                    <TransitionGroup name="fade-up" tag="div">
                        <div v-for="offer in filteredOffers" :key="offer.id"
                             class="card flight-card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap gap-3">

                                    <!-- Airline logo + name -->
                                    <div class="text-center flex-shrink-0" style="width:72px">
                                        <div class="avatar avatar-lg mx-auto mb-1 border bg-light rounded d-flex align-items-center justify-content-center p-1">
                                            <img :src="airlineLogoUrl(offer.airline_iata)"
                                                 :alt="offer.airline_name"
                                                 class="img-fluid"
                                                 style="max-height:36px;object-fit:contain"
                                                 @error="($event.target as HTMLImageElement).style.display='none'" />
                                        </div>
                                        <p class="fs-12 text-muted mb-0 text-truncate">{{ offer.airline_name }}</p>
                                    </div>

                                    <!-- Route timeline -->
                                    <div class="flex-fill">
                                        <div class="d-flex align-items-center gap-3">
                                            <!-- Depart -->
                                            <div class="text-center">
                                                <h4 class="fw-bold mb-0">{{ formatTime(offer.departs_at) }}</h4>
                                                <p class="fs-12 fw-medium text-muted mb-0">{{ offer.origin }}</p>
                                            </div>

                                            <!-- Duration line -->
                                            <div class="flex-fill text-center">
                                                <p class="fs-12 text-muted mb-1">{{ formatDuration(offer.duration) }}</p>
                                                <div class="position-relative d-flex align-items-center mb-1">
                                                    <hr class="flex-fill m-0">
                                                    <span class="position-absolute start-50 translate-middle-x bg-white px-1">
                                                        <i class="isax isax-airplane rotate-45 text-primary fs-14"></i>
                                                    </span>
                                                </div>
                                                <span :class="['badge fs-11', offer.stops === 0 ? 'bg-outline-success' : offer.stops === 1 ? 'bg-warning text-dark' : 'bg-danger']">
                                                    {{ offer.stop_label }}
                                                </span>
                                            </div>

                                            <!-- Arrive -->
                                            <div class="text-center">
                                                <h4 class="fw-bold mb-0">{{ formatTime(offer.arrives_at) }}</h4>
                                                <p class="fs-12 fw-medium text-muted mb-0">{{ offer.destination }}</p>
                                            </div>
                                        </div>
                                        <p class="fs-12 text-center text-muted mt-1 mb-0">
                                            {{ formatDate(offer.departs_at) }} · {{ offer.flight_number }}
                                        </p>
                                    </div>

                                    <!-- Price + actions -->
                                    <div class="text-end border-start ps-3 flex-shrink-0">
                                        <p class="fs-12 text-muted mb-0">from</p>
                                        <h4 class="text-primary fw-bold mb-0">
                                            {{ formatPrice(offer.price, offer.currency) }}
                                        </h4>
                                        <p class="fs-12 text-muted mb-2">per person</p>
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <button @click="bookFlight(offer)"
                                                    class="btn btn-primary btn-sm">
                                                Select
                                            </button>
                                            <button @click="toggleSave(offer)"
                                                    :class="['btn btn-sm', isSaved(offer.id) ? 'btn-danger' : 'btn-light']"
                                                    :title="isSaved(offer.id) ? 'Unsave' : 'Save'">
                                                <i :class="['isax', isSaved(offer.id) ? 'isax-heart5' : 'isax-heart']"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <!-- Tags strip -->
                                <div class="d-flex flex-wrap gap-2 border-top pt-2 mt-2">
                                    <span v-if="offer.stops === 0"
                                          class="badge bg-primary-light text-primary fs-11">
                                        <i class="isax isax-flash-circle me-1"></i>Direct
                                    </span>
                                    <span v-if="offer.baggages.carry_on"
                                          class="badge bg-outline-success fs-11">
                                        <i class="isax isax-bag-2 me-1"></i>Carry-on
                                    </span>
                                    <span v-if="offer.baggages.checked"
                                          class="badge bg-outline-success fs-11">
                                        <i class="isax isax-bag-tick me-1"></i>Checked bag
                                    </span>
                                    <span v-if="!offer.baggages.checked"
                                          class="badge bg-outline-danger fs-11">
                                        No free bag
                                    </span>
                                    <span class="badge bg-white border text-gray-9 fs-11">
                                        {{ cabinLabels[offer.cabin_class] ?? offer.cabin_class }}
                                    </span>
                                </div>

                            </div>
                        </div>
                    </TransitionGroup>

                    <!-- Bottom CTA -->
                    <div v-if="filteredOffers.length >= 8"
                         class="bg-info br-10 p-4 text-center mt-2">
                        <p class="fw-medium mb-1">Didn't find the right flight?</p>
                        <p class="fs-14 text-muted mb-3">Shifting your dates by ±2 days often unlocks better fares.</p>
                        <button @click="modifySearch"
                                class="btn btn-primary btn-sm">
                            <i class="isax isax-calendar-2 me-2"></i>Try different dates
                        </button>
                    </div>

                </div>
                <!-- /Results area -->

            </div>
        </div>
    </div>
</template>

<style scoped>
.flight-card {
    animation: fadeUp 0.3s both;
    transition: box-shadow 0.2s, transform 0.2s;
}
.flight-card:hover {
    transform: translateY(-2px);
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up-enter-active { transition: all 0.25s ease; }
.fade-up-enter-from  { opacity: 0; transform: translateY(8px); }
</style>
