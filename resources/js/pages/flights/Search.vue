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

    <!-- ── Search summary bar ─────────────────────────────────────────── -->
    <div class="sticky top-16 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
            <!-- Route pill -->
            <div class="flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                <PlaneTakeoff class="h-4 w-4" />
                {{ params.origin }}
                <ArrowLeftRight class="h-3.5 w-3.5 text-blue-400" />
                {{ params.destination }}
            </div>

            <!-- Date pill -->
            <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                <Clock class="h-3.5 w-3.5 text-gray-400" />
                {{ departsLabel }}
                <span v-if="params.trip_type === 'round-trip' && params.return_date" class="text-gray-400"> · {{ tripLabel }}</span>
            </div>

            <!-- Passengers -->
            <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                <Users class="h-3.5 w-3.5 text-gray-400" />
                {{ params.adults }} adult{{ params.adults > 1 ? 's' : '' }}
                · {{ cabinLabels[params.cabin] ?? params.cabin }}
            </div>

            <!-- Modify search button -->
            <button @click="modifySearch"
                    class="ml-auto flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                <Search class="h-3.5 w-3.5" />
                Modify search
            </button>
        </div>
    </div>

    <!-- ── Main content ──────────────────────────────────────────────── -->
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

        <!-- Mobile filter toggle -->
        <div class="mb-4 flex items-center justify-between lg:hidden">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-900">{{ filteredOffers.length }}</span> flights found
            </p>
            <button @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-300 hover:bg-blue-50">
                <SlidersHorizontal class="h-4 w-4" />
                Filters
                <span v-if="filterStops !== null || filterAirlines.length > 0"
                      class="flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                    {{ (filterStops !== null ? 1 : 0) + filterAirlines.length }}
                </span>
            </button>
        </div>

        <div class="flex gap-6">

            <!-- ── Sidebar ──────────────────────────────────────────── -->
            <aside :class="[
                'w-64 flex-shrink-0',
                showFilters ? 'block' : 'hidden lg:block',
                'sticky top-32 self-start'
            ]">

                <!-- Price alert card -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-900">
                        <Bell class="h-4 w-4 text-blue-500" />
                        Price Alert
                    </div>
                    <p class="mb-3 text-xs text-gray-500">Get notified when prices drop for this route.</p>
                    <template v-if="!alertSet">
                        <input v-model="alertEmail" type="email" placeholder="your@email.com"
                               class="mb-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs focus:border-blue-500 focus:outline-none" />
                        <button @click="setPriceAlert"
                                class="w-full rounded-lg border border-blue-100 bg-blue-50 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            Set Alert
                        </button>
                    </template>
                    <div v-else class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">
                        ✓ Alert set! We'll email you.
                    </div>
                </div>

                <!-- Stops filter -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-gray-900">Stops</div>
                    <div class="flex flex-col gap-2">
                        <label v-for="opt in [{ value: null, label: 'Any' }, { value: 0, label: 'Direct only' }, { value: 1, label: '1 stop max' }, { value: 2, label: '2 stops max' }]"
                               :key="String(opt.value)"
                               class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="stops"
                                   :value="opt.value"
                                   v-model="filterStops"
                                   class="accent-blue-600" />
                            <span class="text-sm text-gray-700">{{ opt.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Airlines filter -->
                <div v-if="allAirlines.length > 1" class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-gray-900">Airlines</div>
                    <div class="flex flex-col gap-2">
                        <label v-for="al in allAirlines" :key="al.iata"
                               class="flex cursor-pointer items-center gap-2.5">
                            <input type="checkbox"
                                   :checked="filterAirlines.includes(al.iata)"
                                   @change="toggleAirline(al.iata)"
                                   class="accent-blue-600 rounded" />
                            <img :src="airlineLogoUrl(al.iata)" :alt="al.name"
                                 class="h-4 w-6 object-contain" @error="($event.target as HTMLImageElement).style.display='none'" />
                            <span class="text-sm text-gray-700 truncate">{{ al.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Price range filter -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between text-sm font-semibold text-gray-900">
                        Max price
                        <span class="font-normal text-blue-600">{{ formatPrice(filterMaxPrice, params.currency) }}</span>
                    </div>
                    <input type="range" :min="0" :max="maxPrice" step="10" v-model.number="filterMaxPrice"
                           class="w-full accent-blue-600" />
                    <div class="mt-1 flex justify-between text-xs text-gray-400">
                        <span>{{ params.currency }} 0</span>
                        <span>{{ formatPrice(maxPrice, params.currency) }}</span>
                    </div>
                </div>

            </aside>

            <!-- ── Results area ─────────────────────────────────────── -->
            <div class="min-w-0 flex-1">

                <!-- Sort bar -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                            Showing
                            <span class="text-blue-600">{{ filteredOffers.length }}</span>
                            flights
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ routeLabel }} · {{ departsLabel }} · {{ tripLabel }}
                            <span v-if="meta.is_mock" class="ml-1.5 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">
                                Demo data
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Sort by:</span>
                        <div class="relative">
                            <select v-model="sortBy"
                                    class="cursor-pointer appearance-none rounded-lg border border-gray-200 bg-white py-1.5 pl-3 pr-8 text-sm font-medium text-gray-700 focus:border-blue-500 focus:outline-none">
                                <option value="price">Cheapest first</option>
                                <option value="duration">Shortest flight</option>
                                <option value="departure">Earliest departure</option>
                                <option value="arrival">Earliest arrival</option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredOffers.length === 0"
                     class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-16 text-center">
                    <div class="mb-4 text-5xl">🔍</div>
                    <p class="text-base font-bold text-gray-900">No flights found</p>
                    <p class="mt-1 text-sm text-gray-500">Try different dates, filters, or fewer passengers.</p>
                    <button @click="modifySearch"
                            class="mt-5 rounded-xl px-6 py-2.5 text-sm font-semibold text-white"
                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                        New search
                    </button>
                </div>

                <!-- Flight cards -->
                <TransitionGroup name="fade-up" tag="div" class="flex flex-col gap-2.5">
                    <article
                        v-for="(offer, idx) in filteredOffers"
                        :key="offer.id"
                        :style="{ animationDelay: `${idx * 0.04}s` }"
                        class="flight-card overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-px hover:border-blue-300 hover:shadow-md"
                    >
                        <!-- Main row -->
                        <div class="flex flex-wrap items-center gap-4 px-5 py-4">

                            <!-- Airline logo + name -->
                            <div class="flex w-16 flex-shrink-0 flex-col items-center gap-1">
                                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-gray-50">
                                    <img :src="airlineLogoUrl(offer.airline_iata)"
                                         :alt="offer.airline_name"
                                         class="h-full w-full object-contain p-1"
                                         @error="($event.target as HTMLImageElement).style.display='none'" />
                                    <span class="hidden text-[10px] font-black text-blue-700"
                                          :class="{ '!inline': false }">{{ offer.airline_iata }}</span>
                                </div>
                                <span class="max-w-[72px] text-center text-[10px] leading-tight text-gray-500">
                                    {{ offer.airline_name }}
                                </span>
                            </div>

                            <!-- Route: depart time → arrow → arrive time, IATA codes below -->
                            <div class="min-w-[180px] flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-bold text-gray-900">{{ formatTime(offer.departs_at) }}</span>
                                    <div class="flex min-w-0 flex-1 flex-col items-center gap-0.5">
                                        <div class="h-px w-full bg-gray-200"></div>
                                        <span class="text-[10px] text-gray-400">{{ formatDuration(offer.duration) }}</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900">{{ formatTime(offer.arrives_at) }}</span>
                                </div>
                                <div class="mt-1 flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">{{ offer.origin }}</span>
                                    <span class="text-[10px] text-gray-400">{{ formatDate(offer.departs_at) }}</span>
                                    <span class="text-xs font-medium text-gray-500">{{ offer.destination }}</span>
                                </div>
                            </div>

                            <!-- Stops + flight number -->
                            <div class="flex w-20 flex-shrink-0 flex-col items-center gap-1.5">
                                <span :class="['rounded-full border px-2.5 py-0.5 text-[11px] font-semibold', stopBadge(offer.stops)]">
                                    {{ offer.stop_label }}
                                </span>
                                <span class="text-[10px] text-gray-400">{{ offer.flight_number }}</span>
                            </div>

                            <!-- Price + book button -->
                            <div class="ml-auto flex flex-shrink-0 flex-col items-end gap-1">
                                <span class="text-[10px] text-gray-400">{{ offer.currency }}</span>
                                <span class="font-extrabold leading-none text-gray-900"
                                      style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.5rem;">
                                    {{ formatPrice(offer.price, offer.currency) }}
                                </span>
                                <span class="text-[10px] text-gray-400">per person</span>

                                <div class="mt-1 flex items-center gap-1.5">
                                    <button @click="bookFlight(offer)"
                                            class="rounded-lg px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md"
                                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                                        Select
                                    </button>
                                    <button @click="toggleSave(offer)"
                                            :class="[
                                                'flex h-8 w-8 items-center justify-center rounded-full border transition',
                                                isSaved(offer.id)
                                                    ? 'border-red-200 bg-red-50 text-red-500'
                                                    : 'border-gray-200 bg-white text-gray-400 hover:border-red-200 hover:bg-red-50 hover:text-red-400'
                                            ]"
                                            :title="isSaved(offer.id) ? 'Remove from saved' : 'Save flight'">
                                        <Heart class="h-3.5 w-3.5" :fill="isSaved(offer.id) ? 'currentColor' : 'none'" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tags bar -->
                        <div class="flex flex-wrap gap-1.5 border-t border-gray-100 bg-gray-50 px-5 py-2.5">
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[11px] text-gray-600">
                                ✈ {{ offer.flight_number }}
                            </span>
                            <span v-if="offer.baggages.carry_on"
                                  class="rounded-md border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700">
                                🧳 Carry-on included
                            </span>
                            <span v-if="offer.baggages.checked"
                                  class="rounded-md border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700">
                                ✓ Checked bag
                            </span>
                            <span v-if="!offer.baggages.checked"
                                  class="rounded-md border border-red-100 bg-red-50 px-2 py-0.5 text-[11px] text-red-600">
                                ✗ No free bag
                            </span>
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[11px] text-gray-600 capitalize">
                                {{ cabinLabels[offer.cabin_class] ?? offer.cabin_class }}
                            </span>
                            <span v-if="offer.stops === 0"
                                  class="rounded-md border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] text-blue-700">
                                ⚡ Direct flight
                            </span>
                        </div>
                    </article>
                </TransitionGroup>

                <!-- Bottom CTA when many results -->
                <div v-if="filteredOffers.length >= 8"
                     class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-5 text-center">
                    <p class="text-sm font-medium text-blue-800">
                        Didn't find the perfect flight?
                    </p>
                    <p class="mt-0.5 text-xs text-blue-600">Try adjusting your dates by ±2 days for better prices.</p>
                    <button @click="modifySearch"
                            class="mt-3 rounded-lg border border-blue-200 bg-white px-5 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                        Try different dates
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.flight-card { animation: fadeUp 0.35s both; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.fade-up-enter-active { transition: all 0.3s ease; }
.fade-up-enter-from  { opacity: 0; transform: translateY(10px); }
</style>
