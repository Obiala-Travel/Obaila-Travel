<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import {
    MapPin, Star, Wifi, Car, UtensilsCrossed, Waves,
    Dumbbell, ChevronDown, SlidersHorizontal, Search,
    Users, CalendarDays, Lightbulb,
} from 'lucide-vue-next';
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
    { key: 'Pool',      label: 'Pool',              icon: '🛁' },
    { key: 'WiFi',      label: 'Free WiFi',         icon: '📶' },
    { key: 'Parking',   label: 'Parking',           icon: '🅿️' },
    { key: 'Breakfast', label: 'Breakfast',         icon: '🍳' },
    { key: 'Gym',       label: 'Gym / Fitness',     icon: '💪' },
    { key: 'Spa',       label: 'Spa',               icon: '🧖' },
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
    Exceptional: 'bg-emerald-600',
    Excellent:   'bg-blue-700',
    'Very Good': 'bg-blue-600',
    Good:        'bg-blue-500',
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
const nightsLabel = computed(() =>
    `${meta.value.nights} night${meta.value.nights !== 1 ? 's' : ''}`
);
const meta = computed(() => props.meta);
</script>

<template>
    <Head :title="`Hotels in ${params.destination} — Obiala`" />

    <!-- ── Search summary bar ─────────────────────────────────────────── -->
    <div class="sticky top-16 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3 sm:px-6">

            <!-- Destination pill -->
            <div class="flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                🏨 {{ params.destination }}
            </div>

            <!-- Dates pill -->
            <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                <CalendarDays class="h-3.5 w-3.5 text-gray-400" />
                {{ formatDate(params.checkin) }} → {{ formatDate(params.checkout) }}
                <span class="ml-1 text-gray-400">· {{ nightsLabel }}</span>
            </div>

            <!-- Guests pill -->
            <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                <Users class="h-3.5 w-3.5 text-gray-400" />
                {{ params.guests }} guest{{ params.guests > 1 ? 's' : '' }}
                · {{ params.rooms }} room{{ params.rooms > 1 ? 's' : '' }}
            </div>

            <!-- Modify search -->
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
                <span class="font-semibold text-gray-900">{{ filteredHotels.length }}</span> properties found
            </p>
            <button @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-300 hover:bg-blue-50">
                <SlidersHorizontal class="h-4 w-4" />
                Filters
            </button>
        </div>

        <div class="flex gap-6">

            <!-- ── Sidebar ──────────────────────────────────────────── -->
            <aside :class="['w-64 flex-shrink-0 sticky top-32 self-start', showFilters ? 'block' : 'hidden lg:block']">

                <!-- Price filter -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between text-sm font-semibold text-gray-900">
                        Max price / night
                        <span class="font-normal text-blue-600">
                            {{ filterMaxPrice >= maxPrice ? 'Any' : formatPrice(filterMaxPrice, params.currency) }}
                        </span>
                    </div>
                    <input type="range" :min="0" :max="maxPrice" step="5" v-model.number="filterMaxPrice"
                           class="w-full accent-blue-600" />
                    <div class="mt-1 flex justify-between text-xs text-gray-400">
                        <span>{{ params.currency }} 0</span>
                        <span>{{ formatPrice(maxPrice, params.currency) }}</span>
                    </div>
                </div>

                <!-- Star rating filter -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-gray-900">Min. star rating</div>
                    <div class="flex flex-col gap-2">
                        <label v-for="s in [{ v: 0, l: 'Any' }, { v: 3, l: '3+ Stars' }, { v: 4, l: '4+ Stars' }, { v: 5, l: '5 Stars only' }]"
                               :key="s.v" class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="stars" :value="s.v" v-model.number="filterStars" class="accent-blue-600" />
                            <span class="text-sm text-gray-700">{{ s.l }}</span>
                        </label>
                    </div>
                </div>

                <!-- Amenities filter -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-gray-900">Amenities</div>
                    <div class="flex flex-col gap-2">
                        <label v-for="am in AMENITY_FILTERS" :key="am.key"
                               class="flex cursor-pointer items-center gap-2.5">
                            <input type="checkbox"
                                   :checked="filterAmenities.includes(am.key)"
                                   @change="toggleAmenity(am.key)"
                                   class="accent-blue-600 rounded" />
                            <span class="text-sm text-gray-700">{{ am.icon }} {{ am.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Travel tip card -->
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-800">
                        <Lightbulb class="h-4 w-4" />
                        Travel Tip
                    </div>
                    <p class="text-xs leading-relaxed text-amber-700">
                        Book midweek for up to 30% savings. Prices are lowest on Tuesdays and Wednesdays.
                    </p>
                </div>

            </aside>

            <!-- ── Results area ─────────────────────────────────────── -->
            <div class="min-w-0 flex-1">

                <!-- Sort bar -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900">
                            <span class="text-blue-600">{{ filteredHotels.length }}</span> properties in
                            {{ params.destination }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ formatDate(params.checkin) }} – {{ formatDate(params.checkout) }}
                            · {{ nightsLabel }} · {{ params.guests }} guest{{ params.guests > 1 ? 's' : '' }}
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
                                <option value="price">Price ↑</option>
                                <option value="rating">Rating ↓</option>
                                <option value="stars">Stars ↓</option>
                                <option value="name">Name A–Z</option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredHotels.length === 0"
                     class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-16 text-center">
                    <div class="mb-4 text-5xl">🏨</div>
                    <p class="text-base font-bold text-gray-900">No properties found</p>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or different dates.</p>
                    <button @click="modifySearch"
                            class="mt-5 rounded-xl px-6 py-2.5 text-sm font-semibold text-white"
                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                        New search
                    </button>
                </div>

                <!-- Hotel cards -->
                <TransitionGroup name="fade-up" tag="div" class="flex flex-col gap-3">
                    <article
                        v-for="(hotel, idx) in filteredHotels"
                        :key="hotel.id"
                        :style="{ animationDelay: `${idx * 0.05}s` }"
                        class="hotel-card flex overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-px hover:border-blue-300 hover:shadow-md"
                    >
                        <!-- Thumbnail -->
                        <div class="relative flex w-36 flex-shrink-0 items-center justify-center bg-gray-100 text-5xl sm:w-44">
                            {{ hotel.emoji }}
                            <span v-if="hotel.free_cancel"
                                  class="absolute left-2 top-2 rounded bg-emerald-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                Free cancel
                            </span>
                        </div>

                        <!-- Body -->
                        <div class="flex min-w-0 flex-1 flex-col gap-2 p-4">
                            <h3 class="truncate font-bold text-gray-900">{{ hotel.name }}</h3>

                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <MapPin class="h-3 w-3 flex-shrink-0" />
                                {{ hotel.destination }} · {{ TYPE_LABELS[hotel.type] ?? hotel.type }}
                            </div>

                            <!-- Stars + score -->
                            <div class="flex items-center gap-3">
                                <div class="flex gap-0.5">
                                    <span v-for="(filled, si) in starArray(hotel.stars)" :key="si"
                                          :class="filled ? 'text-amber-400' : 'text-gray-200'"
                                          class="text-sm">★</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span :class="['rounded px-1.5 py-0.5 text-xs font-bold text-white', ratingBg[hotel.rating_label] ?? 'bg-blue-600']">
                                        {{ hotel.rating }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ hotel.rating_label }}</span>
                                </div>
                            </div>

                            <!-- Amenity chips -->
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="am in hotel.amenities" :key="am"
                                      class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">
                                    {{ am }}
                                </span>
                            </div>
                        </div>

                        <!-- Price column -->
                        <div class="flex flex-shrink-0 flex-col items-end justify-center border-l border-gray-100 p-4 sm:min-w-[140px]">
                            <span class="text-[10px] text-gray-400">per night</span>
                            <span class="font-extrabold leading-none text-gray-900"
                                  class="text-2xl font-extrabold">
                                {{ formatPrice(hotel.price_per_night, hotel.currency) }}
                            </span>
                            <span class="mt-0.5 text-[11px] text-gray-400">
                                {{ formatPrice(hotel.total_price, hotel.currency) }} total
                            </span>

                            <button @click="bookHotel(hotel)"
                                    class="mt-3 rounded-lg px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md"
                                    style="background:#1c64f2">
                                Book now
                            </button>

                            <span v-if="hotel.free_cancel"
                                  class="mt-1.5 text-[11px] font-semibold text-emerald-600">
                                ✓ Free cancellation
                            </span>
                        </div>
                    </article>
                </TransitionGroup>

            </div>
        </div>
    </div>
</template>

<style scoped>
.hotel-card { animation: fadeUp 0.3s both; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.fade-up-enter-active { transition: all 0.3s ease; }
.fade-up-enter-from  { opacity: 0; transform: translateY(10px); }
</style>
