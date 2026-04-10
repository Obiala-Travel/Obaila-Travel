<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import {
    PlaneTakeoff, ArrowLeftRight, Heart, Bell,
    SlidersHorizontal, ChevronDown, Search, X,
    Clock, Users, Plane,
} from 'lucide-vue-next';
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
    if (!mins) return iso; // return raw if unparseable
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
    // Duffel / public airline logo service
    return `https://pics.avs.io/60/60/${iata}.png`;
}

function stopBadge(stops: number) {
    if (stops === 0) return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    if (stops === 1) return 'bg-amber-50 text-amber-700 border-amber-100';
    return 'bg-red-50 text-red-600 border-red-100';
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

// ── Book ───────────────────────────────────────────────────────────────────
function bookFlight(offer: Offer) {
    router.post('/checkout/flight/select', { offer });
}

// ── Modify search ──────────────────────────────────────────────────────────
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

    <!-- ── Breadcrumb bar ──────────────────────────────────────────────── -->
    <div class="border-b border-gray-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- Route + meta pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white">
                        <PlaneTakeoff class="h-4 w-4" />
                        {{ params.origin }} → {{ params.destination }}
                    </div>
                    <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600">
                        <Clock class="h-3.5 w-3.5 text-gray-400" />
                        {{ departsLabel }}
                        <span v-if="params.trip_type === 'round-trip' && params.return_date" class="text-gray-400"> · {{ tripLabel }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600">
                        <Users class="h-3.5 w-3.5 text-gray-400" />
                        {{ params.adults }} adult{{ params.adults > 1 ? 's' : '' }} · {{ cabinLabels[params.cabin] ?? params.cabin }}
                    </div>
                </div>
                <button @click="modifySearch"
                        class="flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700">
                    <Search class="h-3.5 w-3.5" /> Modify search
                </button>
            </div>
        </div>
    </div>

    <!-- ── Page body ────────────────────────────────────────────────────── -->
    <div class="bg-gray-50 min-h-screen">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

            <!-- Mobile filter toggle -->
            <div class="mb-4 flex items-center justify-between lg:hidden">
                <p class="text-sm text-gray-500">
                    <span class="font-bold text-gray-900">{{ filteredOffers.length }}</span> flights found
                </p>
                <button @click="showFilters = !showFilters"
                        class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">
                    <SlidersHorizontal class="h-4 w-4" />
                    Filters
                    <span v-if="filterStops !== null || filterAirlines.length > 0"
                          class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                        {{ (filterStops !== null ? 1 : 0) + filterAirlines.length }}
                    </span>
                </button>
            </div>

            <div class="flex gap-5">

                <!-- ── Sidebar ──────────────────────────────────────────── -->
                <aside :class="[
                    'w-64 flex-shrink-0',
                    showFilters ? 'block' : 'hidden lg:block',
                    'sticky top-4 self-start space-y-3'
                ]">

                    <!-- Filter header -->
                    <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <h2 class="text-sm font-bold text-gray-900">Filters</h2>
                        <button @click="filterStops = null; filterAirlines = []; filterMaxPrice = maxPrice"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                            Reset all
                        </button>
                    </div>

                    <!-- Price alert -->
                    <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-4 shadow-sm">
                        <div class="mb-1.5 flex items-center gap-2">
                            <Bell class="h-4 w-4 text-blue-600" />
                            <span class="text-sm font-bold text-gray-900">Price Alert</span>
                        </div>
                        <p class="mb-3 text-xs text-gray-500">Get notified when fares drop on this route.</p>
                        <template v-if="!alertSet">
                            <input v-model="alertEmail" type="email" placeholder="your@email.com"
                                   class="mb-2 w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs focus:border-blue-500 focus:outline-none" />
                            <button @click="setPriceAlert"
                                    class="w-full rounded-lg bg-blue-600 py-2 text-xs font-bold text-white transition hover:bg-blue-700">
                                Set Alert
                            </button>
                        </template>
                        <div v-else class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            ✓ Alert set! We'll email you.
                        </div>
                    </div>

                    <!-- Stops -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-400">Stops</h3>
                        <div class="flex flex-col gap-2.5">
                            <label v-for="opt in [
                                { value: null, label: 'Any number of stops' },
                                { value: 0,    label: 'Direct only' },
                                { value: 1,    label: '1 stop max' },
                                { value: 2,    label: '2+ stops' }
                            ]" :key="String(opt.value)"
                               class="flex cursor-pointer items-center gap-3 group">
                                <input type="radio" name="stops" :value="opt.value" v-model="filterStops"
                                       class="accent-blue-600" />
                                <span class="text-sm text-gray-700 group-hover:text-blue-600">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Airlines -->
                    <div v-if="allAirlines.length > 1" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-400">Airlines</h3>
                        <div class="flex flex-col gap-2.5">
                            <label v-for="al in allAirlines" :key="al.iata"
                                   class="flex cursor-pointer items-center gap-2.5 group">
                                <input type="checkbox"
                                       :checked="filterAirlines.includes(al.iata)"
                                       @change="toggleAirline(al.iata)"
                                       class="accent-blue-600 rounded" />
                                <img :src="airlineLogoUrl(al.iata)" :alt="al.name"
                                     class="h-5 w-7 object-contain"
                                     @error="($event.target as HTMLImageElement).style.display='none'" />
                                <span class="truncate text-sm text-gray-700 group-hover:text-blue-600">{{ al.name }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Max price -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Max price</h3>
                            <span class="text-sm font-bold text-blue-600">{{ formatPrice(filterMaxPrice, params.currency) }}</span>
                        </div>
                        <input type="range" :min="0" :max="maxPrice" step="10" v-model.number="filterMaxPrice"
                               class="w-full accent-blue-600" />
                        <div class="mt-1.5 flex justify-between text-xs text-gray-400">
                            <span>{{ params.currency }} 0</span>
                            <span>{{ formatPrice(maxPrice, params.currency) }}</span>
                        </div>
                    </div>

                </aside>

                <!-- ── Results area ─────────────────────────────────────── -->
                <div class="min-w-0 flex-1">

                    <!-- Results header -->
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-5 py-3.5 shadow-sm">
                        <div>
                            <p class="text-sm font-bold text-gray-900">
                                <span class="text-blue-600">{{ filteredOffers.length }}</span>
                                flight{{ filteredOffers.length !== 1 ? 's' : '' }} found
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ routeLabel }} · {{ departsLabel }} · {{ tripLabel }}
                                <span v-if="meta.is_mock"
                                      class="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
                                    Demo data
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">Sort:</span>
                            <div class="relative">
                                <select v-model="sortBy"
                                        class="cursor-pointer appearance-none rounded-lg border border-gray-200 bg-white py-1.5 pl-3 pr-8 text-sm font-semibold text-gray-700 focus:border-blue-500 focus:outline-none">
                                    <option value="price">Cheapest first</option>
                                    <option value="duration">Shortest flight</option>
                                    <option value="departure">Earliest departure</option>
                                    <option value="arrival">Earliest arrival</option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Sign-in nudge -->
                    <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                        <p class="text-xs font-medium text-blue-800">
                            💡 Sign in to save flights and get fare alerts sent straight to your inbox.
                        </p>
                        <a href="/login" class="flex-shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-blue-700 shadow-sm transition hover:bg-blue-100">
                            Sign in
                        </a>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredOffers.length === 0"
                         class="flex flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white py-20 text-center shadow-sm">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-3xl">✈</div>
                        <h3 class="text-base font-bold text-gray-900">No flights match your filters</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting stops, airlines, or your price limit.</p>
                        <button @click="modifySearch"
                                class="mt-6 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:opacity-90"
                                style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                            New search
                        </button>
                    </div>

                    <!-- Flight cards -->
                    <TransitionGroup name="fade-up" tag="div" class="flex flex-col gap-3">
                        <article
                            v-for="(offer, idx) in filteredOffers"
                            :key="offer.id"
                            :style="{ animationDelay: `${idx * 0.04}s` }"
                            class="flight-card overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                        >
                            <!-- Main row -->
                            <div class="flex flex-wrap items-center gap-5 px-5 py-5">

                                <!-- Airline -->
                                <div class="flex w-20 flex-shrink-0 flex-col items-center gap-1.5">
                                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 shadow-sm">
                                        <img :src="airlineLogoUrl(offer.airline_iata)" :alt="offer.airline_name"
                                             class="h-full w-full object-contain p-1.5"
                                             @error="($event.target as HTMLImageElement).style.display='none'" />
                                    </div>
                                    <span class="text-center text-[11px] font-medium leading-tight text-gray-500">
                                        {{ offer.airline_name }}
                                    </span>
                                </div>

                                <!-- Route timeline -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-3">
                                        <!-- Depart -->
                                        <div class="text-center">
                                            <p class="text-2xl font-extrabold leading-none text-gray-900">{{ formatTime(offer.departs_at) }}</p>
                                            <p class="mt-1 text-xs font-bold text-gray-500">{{ offer.origin }}</p>
                                        </div>

                                        <!-- Duration line -->
                                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                                            <span class="text-[11px] font-medium text-gray-400">{{ formatDuration(offer.duration) }}</span>
                                            <div class="relative flex w-full items-center">
                                                <div class="h-px flex-1 bg-gray-200"></div>
                                                <Plane class="mx-1 h-3.5 w-3.5 flex-shrink-0 text-blue-400" />
                                                <div class="h-px flex-1 bg-gray-200"></div>
                                            </div>
                                            <span :class="[
                                                'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                offer.stops === 0 ? 'bg-emerald-50 text-emerald-700' :
                                                offer.stops === 1 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600'
                                            ]">{{ offer.stop_label }}</span>
                                        </div>

                                        <!-- Arrive -->
                                        <div class="text-center">
                                            <p class="text-2xl font-extrabold leading-none text-gray-900">{{ formatTime(offer.arrives_at) }}</p>
                                            <p class="mt-1 text-xs font-bold text-gray-500">{{ offer.destination }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-center text-[11px] text-gray-400">{{ formatDate(offer.departs_at) }} · {{ offer.flight_number }}</p>
                                </div>

                                <!-- Price + actions -->
                                <div class="flex flex-shrink-0 flex-col items-end gap-2 border-l border-gray-100 pl-5">
                                    <div class="text-right">
                                        <p class="text-[11px] font-medium text-gray-400">from</p>
                                        <p class="text-2xl font-extrabold leading-none text-gray-900">
                                            {{ formatPrice(offer.price, offer.currency) }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-gray-400">per person</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="bookFlight(offer)"
                                                class="rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md"
                                                style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                                            Select
                                        </button>
                                        <button @click="toggleSave(offer)"
                                                :class="[
                                                    'flex h-9 w-9 items-center justify-center rounded-xl border transition',
                                                    isSaved(offer.id)
                                                        ? 'border-red-200 bg-red-50 text-red-500'
                                                        : 'border-gray-200 bg-white text-gray-400 hover:border-red-200 hover:bg-red-50 hover:text-red-400'
                                                ]"
                                                :title="isSaved(offer.id) ? 'Unsave' : 'Save flight'">
                                            <Heart class="h-4 w-4" :fill="isSaved(offer.id) ? 'currentColor' : 'none'" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags strip -->
                            <div class="flex flex-wrap items-center gap-1.5 border-t border-gray-100 bg-gray-50/60 px-5 py-2.5">
                                <span v-if="offer.stops === 0"
                                      class="flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                    ⚡ Direct
                                </span>
                                <span v-if="offer.baggages.carry_on"
                                      class="rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">
                                    🧳 Carry-on
                                </span>
                                <span v-if="offer.baggages.checked"
                                      class="rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">
                                    ✓ Checked bag
                                </span>
                                <span v-if="!offer.baggages.checked"
                                      class="rounded-lg bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-600">
                                    ✗ No free bag
                                </span>
                                <span class="rounded-lg bg-white border border-gray-200 px-2.5 py-1 text-[11px] font-medium text-gray-600">
                                    {{ cabinLabels[offer.cabin_class] ?? offer.cabin_class }}
                                </span>
                            </div>
                        </article>
                    </TransitionGroup>

                    <!-- Bottom CTA -->
                    <div v-if="filteredOffers.length >= 8"
                         class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-bold text-blue-900">Didn't find the right flight?</p>
                        <p class="mt-1 text-xs text-blue-600">Shifting your dates by ±2 days often unlocks better fares.</p>
                        <button @click="modifySearch"
                                class="mt-3 rounded-xl border border-blue-200 bg-white px-5 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                            Try different dates
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.flight-card { animation: fadeUp 0.3s both; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.fade-up-enter-active { transition: all 0.25s ease; }
.fade-up-enter-from  { opacity: 0; transform: translateY(8px); }
</style>
