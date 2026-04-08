<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';
import { Lock, AlertCircle } from 'lucide-vue-next';
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

const page       = usePage();
const stripeKey  = computed(() => (page.props as any).stripeKey as string | null);

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
    { code: 'AU', name: 'Australia' }, { code: 'CA', name: 'Canada' },
    { code: 'TR', name: 'Turkey' }, { code: 'BR', name: 'Brazil' },
];

// ── Passenger form ─────────────────────────────────────────────────────────
const passengerCount = 1;
const passengerTemplate = () => ({
    title: 'mr', first_name: '', last_name: '',
    date_of_birth: '', gender: 'm',
    passport_number: '', passport_expiry: '', nationality: 'GB',
});

const form = useForm({
    contact_email: '',
    contact_phone: '',
    passengers: Array.from({ length: passengerCount }, passengerTemplate),
});

const todayStr = new Date().toISOString().split('T')[0];

// ── Stripe state ───────────────────────────────────────────────────────────
const stripe        = ref<Stripe | null>(null);
const elements      = ref<StripeElements | null>(null);
const paymentEl     = ref<HTMLElement | null>(null);
const stripeReady   = ref(false);
const stripeError   = ref('');
const step          = ref<'details' | 'payment'>('details');
const processing    = ref(false);
const generalError  = ref('');

// Step 1: validate passenger details, POST to backend → get client_secret
async function proceedToPayment() {
    generalError.value = '';

    // Trigger Inertia form validation by submitting — but we need JSON back,
    // so we do a manual fetch instead of form.post()
    processing.value = true;

    try {
        // axios is pre-configured with X-XSRF-TOKEN cookie — no manual CSRF handling needed
        const { data } = await axios.post('/checkout/flight', {
            contact_email: form.contact_email,
            contact_phone: form.contact_phone,
            passengers:    form.passengers,
        });

        // Show payment step first so paymentEl div is rendered in the DOM,
        // then mount Stripe into it via nextTick inside mountStripe()
        step.value = 'payment';
        await mountStripe(data.client_secret, data.reference);

    } catch (e: any) {
        // Laravel validation errors: { errors: { field: ['msg'] } }
        if (e.response?.data?.errors) {
            Object.keys(e.response.data.errors).forEach(key => {
                (form.errors as any)[key] = e.response.data.errors[key][0];
            });
            generalError.value = 'Please fix the errors above.';
        } else {
            generalError.value = e.response?.data?.message || 'Something went wrong. Please try again.';
        }
    } finally {
        processing.value = false;
    }
}

// Step 2: mount Stripe Elements after getting client_secret
async function mountStripe(clientSecret: string, reference: string) {
    if (!stripeKey.value) {
        stripeError.value = 'Stripe is not configured.';
        return;
    }

    stripe.value = await loadStripe(stripeKey.value);
    if (!stripe.value) { stripeError.value = 'Failed to load Stripe.'; return; }

    elements.value = stripe.value.elements({
        clientSecret,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#1c64f2',
                fontFamily:   'Inter, ui-sans-serif, system-ui, sans-serif',
                borderRadius: '8px',
            },
        },
    });

    const el = elements.value.create('payment', { layout: 'tabs' });

    // Wait for Vue to flush the DOM update (step = 'payment' renders paymentEl)
    await nextTick();
    if (paymentEl.value) el.mount(paymentEl.value);

    stripeReady.value = true;

    // Store reference so completeFlight route knows which booking to confirm
    sessionStorage.setItem('pending_reference', reference);
}

// Step 3: confirm payment
async function confirmPayment() {
    if (!stripe.value || !elements.value) return;
    processing.value = true;
    stripeError.value = '';

    try {
        const reference = sessionStorage.getItem('pending_reference') ?? '';
        const returnUrl = `${window.location.origin}/checkout/flight/complete?reference=${reference}`;

        const { error } = await stripe.value.confirmPayment({
            elements: elements.value,
            confirmParams: { return_url: returnUrl },
        });

        // Only reached if Stripe did NOT redirect (i.e. there was a payment error)
        if (error) {
            stripeError.value = error.message ?? 'Payment failed. Please try again.';
        }
    } catch (e: any) {
        stripeError.value = e?.message ?? 'An unexpected error occurred. Please try again.';
    } finally {
        // Always re-enable the button so the user can retry
        processing.value = false;
    }
}
</script>

<template>
    <Head :title="`Checkout — ${offer.origin} → ${offer.destination} — Obiala`" />

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <h1 class="mb-2 text-2xl font-bold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
            Complete your booking
        </h1>

        <!-- Step indicator -->
        <div class="mb-6 flex items-center gap-2 text-sm">
            <span :class="['font-semibold', step === 'details' ? 'text-blue-600' : 'text-gray-400']">
                1. Passenger details
            </span>
            <span class="text-gray-300">→</span>
            <span :class="['font-semibold', step === 'payment' ? 'text-blue-600' : 'text-gray-400']">
                2. Payment
            </span>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

            <!-- ── LEFT: Form ────────────────────────────────────── -->
            <div class="min-w-0 flex-1">

                <!-- ── STEP 1: Passenger details ─────────────────── -->
                <template v-if="step === 'details'">

                    <!-- Global error -->
                    <div v-if="generalError"
                         class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                        <AlertCircle class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                        <p class="text-sm font-medium text-red-700">{{ generalError }}</p>
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
                        <p class="mt-2 text-xs text-gray-400">Booking confirmation and e-ticket will be sent here.</p>
                    </section>

                    <!-- Passenger forms -->
                    <section v-for="(pax, idx) in form.passengers" :key="idx"
                             class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
                        <h2 class="mb-4 font-semibold text-gray-900">
                            Passenger {{ idx + 1 }}
                            <span class="ml-2 text-xs font-normal text-gray-400">Adult</span>
                        </h2>

                        <!-- Title / First / Last -->
                        <div class="mb-4 grid gap-3 sm:grid-cols-[100px_1fr_1fr]">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Title *</label>
                                <select v-model="pax.title" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500">
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
                                <p v-if="form.errors[`passengers.${idx}.first_name`]" class="mt-1 text-xs text-red-600">{{ form.errors[`passengers.${idx}.first_name`] }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Last name *</label>
                                <input v-model="pax.last_name" type="text" placeholder="As on passport"
                                       :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                                form.errors[`passengers.${idx}.last_name`] ? 'border-red-400' : 'border-gray-200']" />
                                <p v-if="form.errors[`passengers.${idx}.last_name`]" class="mt-1 text-xs text-red-600">{{ form.errors[`passengers.${idx}.last_name`] }}</p>
                            </div>
                        </div>

                        <!-- DOB / Gender / Nationality -->
                        <div class="mb-4 grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date of birth *</label>
                                <input v-model="pax.date_of_birth" type="date" :max="todayStr"
                                       :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                                form.errors[`passengers.${idx}.date_of_birth`] ? 'border-red-400' : 'border-gray-200']" />
                                <p v-if="form.errors[`passengers.${idx}.date_of_birth`]" class="mt-1 text-xs text-red-600">{{ form.errors[`passengers.${idx}.date_of_birth`] }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Gender *</label>
                                <select v-model="pax.gender" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500">
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

                        <!-- Passport number / Expiry -->
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Passport / ID number *</label>
                                <input v-model="pax.passport_number" type="text" placeholder="e.g. 123456789"
                                       :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                                form.errors[`passengers.${idx}.passport_number`] ? 'border-red-400' : 'border-gray-200']" />
                                <p v-if="form.errors[`passengers.${idx}.passport_number`]" class="mt-1 text-xs text-red-600">{{ form.errors[`passengers.${idx}.passport_number`] }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Passport expiry *</label>
                                <input v-model="pax.passport_expiry" type="date" :min="todayStr"
                                       :class="['w-full rounded-lg border px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100',
                                                form.errors[`passengers.${idx}.passport_expiry`] ? 'border-red-400' : 'border-gray-200']" />
                                <p v-if="form.errors[`passengers.${idx}.passport_expiry`]" class="mt-1 text-xs text-red-600">{{ form.errors[`passengers.${idx}.passport_expiry`] }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Continue button -->
                    <button @click="proceedToPayment" :disabled="processing"
                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold text-white shadow-md transition hover:opacity-90 disabled:opacity-60"
                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                        {{ processing ? 'Saving details...' : `Continue to payment — ${formatPrice(offer.price, offer.currency)}` }}
                    </button>
                    <p class="mt-2 text-center text-xs text-gray-400">🔒 Encrypted and secure. You won't be charged yet.</p>
                </template>

                <!-- ── STEP 2: Stripe Payment Element ─────────────── -->
                <template v-if="step === 'payment'">
                    <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
                        <h2 class="mb-4 font-semibold text-gray-900">Payment details</h2>

                        <!-- Stripe Payment Element mounts here -->
                        <div ref="paymentEl" class="min-h-[120px]">
                            <div v-if="!stripeReady" class="flex items-center gap-2 text-sm text-gray-400">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Loading payment form...
                            </div>
                        </div>

                        <p v-if="stripeError" class="mt-3 text-sm font-medium text-red-600">⚠ {{ stripeError }}</p>
                    </section>

                    <!-- Pay button -->
                    <button @click="confirmPayment" :disabled="processing || !stripeReady"
                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold text-white shadow-md transition hover:opacity-90 disabled:opacity-60"
                            style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                        <Lock class="h-4 w-4" />
                        {{ processing ? 'Processing payment...' : `Pay ${formatPrice(offer.price, offer.currency)}` }}
                    </button>

                    <p class="mt-2 text-center text-xs text-gray-400">
                        🔒 Payments processed securely by Stripe. We never store your card details.
                    </p>

                    <button @click="step = 'details'"
                            class="mt-3 w-full text-center text-sm text-gray-400 underline hover:text-gray-600">
                        ← Back to passenger details
                    </button>
                </template>
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

                    <!-- Details -->
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
                            <span :class="offer.baggages.carry_on ? 'font-medium text-emerald-600' : 'text-gray-400'">
                                {{ offer.baggages.carry_on ? '✓ Included' : 'Not included' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Checked bag</span>
                            <span :class="offer.baggages.checked ? 'font-medium text-emerald-600' : 'text-gray-400'">
                                {{ offer.baggages.checked ? '✓ Included' : 'Not included' }}
                            </span>
                        </div>
                    </div>

                    <div class="my-3 border-t border-gray-100"></div>

                    <div class="flex items-baseline justify-between">
                        <span class="text-sm font-semibold text-gray-700">Total</span>
                        <span class="text-2xl font-extrabold text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                            {{ formatPrice(offer.price, offer.currency) }}
                        </span>
                    </div>
                    <p class="mt-1 text-right text-xs text-gray-400">All taxes and fees included</p>

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
