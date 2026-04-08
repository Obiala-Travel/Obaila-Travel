<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PlaneTakeoff, Clock, Users, ChevronDown, Lock, AlertCircle } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

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
    baggages: { carry_on: boolean; checked: boolean }
}

const props = defineProps<{ offer: Offer }>();

// ── Helpers ────────────────────────────────────────────────────────────────
function formatTime(iso: string) {
    if (!iso) return '--:--';
    return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
}
function formatDate(iso: string) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}
function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price);
}
function parseDuration(iso: string) {
    const m = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?/);
    if (!m) return iso;
    const h = m[1] ?? '0', min = m[2] ?? '0';
    return `${h}h ${min}m`;
}
function airlineLogoUrl(iata: string) {
    return `https://pics.avs.io/60/60/${iata}.png`;
}

// ── COUNTRIES (ISO 3166-1 alpha-2) ────────────────────────────────────────
const COUNTRIES = [
    { code: 'GB', name: 'United Kingdom' }, { code: 'US', name: 'United States' },
    { code: 'NG', name: 'Nigeria' }, { code: 'GH', name: 'Ghana' },
    { code: 'KE', name: 'Kenya' }, { code: 'ZA', name: 'South Africa' },
    { code: 'ET', name: 'Ethiopia' }, { code: 'EG', name: 'Egypt' },
    { code: 'MA', name: 'Morocco' }, { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' }, { code: 'IT', name: 'Italy' },
    { code: 'ES', name: 'Spain' }, { code: 'NL', name: 'Netherlands' },
    { code: 'AE', name: 'UAE' }, { code: 'QA', name: 'Qatar' },
    { code: 'SG', name: 'Singapore' }, { code: 'IN', name: 'India' },
    { code: 'CN', name: 'China' }, { code: 'JP', name: 'Japan' },
    { code: 'AU', name: 'Australia' }, { code: 'CA', name: 'Canada' },
    { code: 'BR', name: 'Brazil' }, { code: 'TR', name: 'Turkey' },
];

// ── Passenger count derived from offer (1 adult default) ──────────────────
// In a real implementation this would come from the original search params.
// We store them in the offer's raw field or default to 1.
const passengerCount = 1;

// ── Form ───────────────────────────────────────────────────────────────────
const passengerTemplate = () => ({
    title: 'mr',
    first_name: '',
    last_name: '',
    date_of_birth: '',
    gender: 'm',
    passport_number: '',
    passport_expiry: '',
    nationality: 'GB',
});

const form = useForm({
    contact_email: '',
    contact_phone: '',
    passengers: Array.from({ length: passengerCount }, passengerTemplate),
});

function submit() {
    form.post('/checkout/flight');
}

const todayStr = new Date().toISOString().split('T')[0];
const cabinLabel: Record<string, string> = {
    economy: 'Economy', premium_economy: 'Premium Economy',
    business: 'Business', first: 'First Class',
    ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Premium Economy',
    BUSINESS: 'Business', FIRST: 'First Class',
};
</script>

<template>
    <Head :title="`Checkout — ${offer.origin} → ${offer.destination} — Obiala`" />

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <h1 class="mb-6 text-2xl font-bold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
            Complete your booking
        </h1>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

            <!-- ── LEFT: Passenger form ──────────────────────────── -->
            <div class="min-w-0 flex-1">

                <!-- Global error -->
                <div v-if="form.errors && Object.keys(form.errors).length"
                     class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                    <AlertCircle class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                    <div>
                        <p class="text-sm font-semibold text-red-700">Please fix the errors below before continuing.</p>
                    </div>
                </div>

                <!-- Contact info -->
                <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
                    <h2 class="mb-4 font-semibold text-gray-900">Contact details</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Email address *</label>
                            <input v-model="form.contact_email" type="email" placeholder="you@email.com"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors.contact_email ? 'border-red-400 bg-red-50' : 'border-gray-200']" />
                            <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">{{ form.errors.contact_email }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Phone number *</label>
                            <input v-model="form.contact_phone" type="tel" placeholder="+44 7700 900000"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors.contact_phone ? 'border-red-400 bg-red-50' : 'border-gray-200']" />
                            <p v-if="form.errors.contact_phone" class="mt-1 text-xs text-red-600">{{ form.errors.contact_phone }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Your booking confirmation and e-ticket will be sent to this email.</p>
                </section>

                <!-- Passenger forms -->
                <section v-for="(pax, idx) in form.passengers" :key="idx"
                         class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
                    <h2 class="mb-4 font-semibold text-gray-900">
                        Passenger {{ idx + 1 }}
                        <span class="ml-2 text-xs font-normal text-gray-400">Adult</span>
                    </h2>

                    <!-- Row 1: Title / First / Last -->
                    <div class="mb-4 grid gap-3 sm:grid-cols-[100px_1fr_1fr]">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Title *</label>
                            <select v-model="pax.title"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500">
                                <option value="mr">Mr</option>
                                <option value="mrs">Mrs</option>
                                <option value="ms">Ms</option>
                                <option value="dr">Dr</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">First name *</label>
                            <input v-model="pax.first_name" type="text" placeholder="As on passport"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors[`passengers.${idx}.first_name`] ? 'border-red-400' : 'border-gray-200']" />
                            <p v-if="form.errors[`passengers.${idx}.first_name`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`passengers.${idx}.first_name`] }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Last name *</label>
                            <input v-model="pax.last_name" type="text" placeholder="As on passport"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors[`passengers.${idx}.last_name`] ? 'border-red-400' : 'border-gray-200']" />
                            <p v-if="form.errors[`passengers.${idx}.last_name`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`passengers.${idx}.last_name`] }}
                            </p>
                        </div>
                    </div>

                    <!-- Row 2: DOB / Gender / Nationality -->
                    <div class="mb-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date of birth *</label>
                            <input v-model="pax.date_of_birth" type="date" :max="todayStr"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors[`passengers.${idx}.date_of_birth`] ? 'border-red-400' : 'border-gray-200']" />
                            <p v-if="form.errors[`passengers.${idx}.date_of_birth`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`passengers.${idx}.date_of_birth`] }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Gender *</label>
                            <select v-model="pax.gender"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500">
                                <option value="m">Male</option>
                                <option value="f">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Nationality *</label>
                            <select v-model="pax.nationality"
                                    :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500',
                                             form.errors[`passengers.${idx}.nationality`] ? 'border-red-400' : 'border-gray-200']">
                                <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Passport number / Expiry -->
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Passport / ID number *</label>
                            <input v-model="pax.passport_number" type="text" placeholder="e.g. 123456789"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors[`passengers.${idx}.passport_number`] ? 'border-red-400' : 'border-gray-200']" />
                            <p v-if="form.errors[`passengers.${idx}.passport_number`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`passengers.${idx}.passport_number`] }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Passport expiry *</label>
                            <input v-model="pax.passport_expiry" type="date" :min="todayStr"
                                   :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                            form.errors[`passengers.${idx}.passport_expiry`] ? 'border-red-400' : 'border-gray-200']" />
                            <p v-if="form.errors[`passengers.${idx}.passport_expiry`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`passengers.${idx}.passport_expiry`] }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Submit -->
                <button @click="submit" :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold text-white shadow-md transition hover:opacity-90 disabled:opacity-60"
                        style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                    <Lock class="h-4 w-4" />
                    {{ form.processing ? 'Processing...' : 'Confirm booking' }}
                </button>
                <p class="mt-2 text-center text-xs text-gray-400">
                    🔒 Your data is encrypted and never shared with third parties.
                </p>
            </div>

            <!-- ── RIGHT: Order summary ───────────────────────────── -->
            <div class="w-full lg:w-80 lg:flex-shrink-0">
                <div class="sticky top-24 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 font-semibold text-gray-900">Order summary</h2>

                    <!-- Airline -->
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-gray-50">
                            <img :src="airlineLogoUrl(offer.airline_iata)" :alt="offer.airline_name"
                                 class="h-full w-full object-contain p-1"
                                 @error="($event.target as HTMLImageElement).style.display='none'" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ offer.airline_name }}</p>
                            <p class="text-xs text-gray-400">{{ offer.flight_number }}</p>
                        </div>
                    </div>

                    <!-- Route -->
                    <div class="mb-4 rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between">
                            <div class="text-center">
                                <p class="text-lg font-bold text-gray-900">{{ formatTime(offer.departs_at) }}</p>
                                <p class="text-xs font-semibold text-gray-500">{{ offer.origin }}</p>
                            </div>
                            <div class="flex flex-col items-center gap-0.5 px-2">
                                <div class="h-px w-16 bg-gray-300"></div>
                                <p class="text-[10px] text-gray-400">{{ parseDuration(offer.duration) }}</p>
                                <p class="text-[10px] text-gray-400">{{ offer.stop_label }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-bold text-gray-900">{{ formatTime(offer.arrives_at) }}</p>
                                <p class="text-xs font-semibold text-gray-500">{{ offer.destination }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-center text-[11px] text-gray-400">{{ formatDate(offer.departs_at) }}</p>
                    </div>

                    <!-- Details list -->
                    <div class="mb-4 flex flex-col gap-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Cabin</span>
                            <span class="font-medium capitalize">{{ cabinLabel[offer.cabin_class] ?? offer.cabin_class }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Passengers</span>
                            <span class="font-medium">{{ passengerCount }} Adult</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Carry-on</span>
                            <span :class="offer.baggages.carry_on ? 'text-emerald-600 font-medium' : 'text-gray-400'">
                                {{ offer.baggages.carry_on ? '✓ Included' : 'Not included' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Checked bag</span>
                            <span :class="offer.baggages.checked ? 'text-emerald-600 font-medium' : 'text-gray-400'">
                                {{ offer.baggages.checked ? '✓ Included' : 'Not included' }}
                            </span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-100"></div>

                    <!-- Total -->
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm font-semibold text-gray-700">Total</span>
                        <span class="text-2xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                            {{ formatPrice(offer.price, offer.currency) }}
                        </span>
                    </div>
                    <p class="mt-1 text-right text-xs text-gray-400">All taxes and fees included</p>

                    <!-- Trust badges -->
                    <div class="mt-4 flex flex-col gap-1.5 text-xs text-gray-400">
                        <span>🔒 Secure 256-bit SSL encryption</span>
                        <span>✈️ Instant booking confirmation</span>
                        <span>📧 E-ticket sent to your email</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
