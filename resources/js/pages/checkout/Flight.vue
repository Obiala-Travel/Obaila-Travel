<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import axios from 'axios';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';
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

defineProps<{ offer: Offer }>();

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

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-05 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="breadcrumb-title mb-2">
                        <i class="isax isax-airplane rotate-45 me-2"></i>
                        {{ offer.origin }} → {{ offer.destination }}
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item"><a href="/flights/search">Flights</a></li>
                            <li class="breadcrumb-item active">Checkout</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /Breadcrumb -->

    <!-- Page Wrapper -->
    <div class="content content-two">
        <div class="container">
            <div class="row">

                <!-- ── Left: Forms ──────────────────────────────────────── -->
                <div class="col-lg-8">

                    <!-- ── STEP 1: Passenger details ──────────────────── -->
                    <template v-if="step === 'details'">

                        <!-- Global error -->
                        <div v-if="generalError"
                             class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                            <i class="isax isax-info-circle flex-shrink-0"></i>
                            {{ generalError }}
                        </div>

                        <div class="card checkout-card mb-4">
                            <div class="card-header">
                                <h5>
                                    Secure Checkout
                                    <i class="isax isax-shield-security ms-2 text-success"></i>
                                </h5>
                            </div>
                            <div class="card-body">

                                <!-- Contact info -->
                                <div class="checkout-title">
                                    <h6 class="mb-2">Contact Details</h6>
                                </div>
                                <div class="checkout-details pb-2 border-bottom mb-4">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input v-model="form.contact_email" type="email"
                                                       placeholder="you@email.com"
                                                       :class="['form-control', form.errors.contact_email ? 'is-invalid' : '']" />
                                                <div v-if="form.errors.contact_email" class="invalid-feedback">{{ form.errors.contact_email }}</div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <input v-model="form.contact_phone" type="tel"
                                                       placeholder="+44 7700 900000"
                                                       :class="['form-control', form.errors.contact_phone ? 'is-invalid' : '']" />
                                                <div v-if="form.errors.contact_phone" class="invalid-feedback">{{ form.errors.contact_phone }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted fs-12 mb-0">Booking confirmation and e-ticket will be sent here.</p>
                                </div>

                                <!-- Passenger forms -->
                                <div v-for="(pax, idx) in form.passengers" :key="idx">
                                    <div class="checkout-title">
                                        <h6 class="mb-2">
                                            Passenger {{ idx + 1 }}
                                            <span class="badge bg-primary ms-2 fs-11">Adult</span>
                                        </h6>
                                    </div>
                                    <div class="checkout-details">
                                        <div class="row">
                                            <div class="col-lg-3 col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                                    <select v-model="pax.title" class="form-select">
                                                        <option value="mr">Mr</option>
                                                        <option value="mrs">Mrs</option>
                                                        <option value="ms">Ms</option>
                                                        <option value="dr">Dr</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                    <input v-model="pax.first_name" type="text" placeholder="As on passport"
                                                           :class="['form-control', form.errors[`passengers.${idx}.first_name`] ? 'is-invalid' : '']" />
                                                    <div v-if="form.errors[`passengers.${idx}.first_name`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.first_name`] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-lg-5 col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                                    <input v-model="pax.last_name" type="text" placeholder="As on passport"
                                                           :class="['form-control', form.errors[`passengers.${idx}.last_name`] ? 'is-invalid' : '']" />
                                                    <div v-if="form.errors[`passengers.${idx}.last_name`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.last_name`] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                                    <input v-model="pax.date_of_birth" type="date" :max="todayStr"
                                                           :class="['form-control', form.errors[`passengers.${idx}.date_of_birth`] ? 'is-invalid' : '']" />
                                                    <div v-if="form.errors[`passengers.${idx}.date_of_birth`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.date_of_birth`] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                                    <select v-model="pax.gender" class="form-select">
                                                        <option value="m">Male</option>
                                                        <option value="f">Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Nationality <span class="text-danger">*</span></label>
                                                    <select v-model="pax.nationality"
                                                            :class="['form-select', form.errors[`passengers.${idx}.nationality`] ? 'is-invalid' : '']">
                                                        <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">{{ c.name }}</option>
                                                    </select>
                                                    <div v-if="form.errors[`passengers.${idx}.nationality`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.nationality`] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Passport / ID Number <span class="text-danger">*</span></label>
                                                    <input v-model="pax.passport_number" type="text" placeholder="e.g. 123456789"
                                                           :class="['form-control', form.errors[`passengers.${idx}.passport_number`] ? 'is-invalid' : '']" />
                                                    <div v-if="form.errors[`passengers.${idx}.passport_number`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.passport_number`] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Passport Expiry <span class="text-danger">*</span></label>
                                                    <input v-model="pax.passport_expiry" type="date" :min="todayStr"
                                                           :class="['form-control', form.errors[`passengers.${idx}.passport_expiry`] ? 'is-invalid' : '']" />
                                                    <div v-if="form.errors[`passengers.${idx}.passport_expiry`]" class="invalid-feedback">{{ form.errors[`passengers.${idx}.passport_expiry`] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Continue button -->
                        <div class="d-grid mb-2">
                            <button @click="proceedToPayment" :disabled="processing"
                                    class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                                <span v-if="processing" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="isax isax-shield-security"></i>
                                {{ processing ? 'Saving details...' : `Continue to Payment — ${formatPrice(offer.price, offer.currency)}` }}
                            </button>
                        </div>
                        <p class="text-center text-muted fs-12">
                            <i class="isax isax-lock me-1"></i>Encrypted and secure. You won't be charged yet.
                        </p>

                    </template>

                    <!-- ── STEP 2: Stripe Payment ──────────────────────── -->
                    <template v-if="step === 'payment'">

                        <div class="card payment-details mb-4">
                            <div class="card-header">
                                <h5>Payment Details</h5>
                            </div>
                            <div class="card-body">
                                <!-- Loading spinner -->
                                <div v-if="!stripeReady"
                                     class="d-flex align-items-center gap-2 text-muted py-4">
                                    <span class="spinner-border spinner-border-sm"></span>
                                    Loading payment form...
                                </div>
                                <!-- Stripe Payment Element mounts here -->
                                <div ref="paymentEl"></div>
                                <p v-if="stripeError" class="mt-3 text-danger fw-medium">
                                    <i class="isax isax-info-circle me-1"></i>{{ stripeError }}
                                </p>
                            </div>
                        </div>

                        <!-- Pay button -->
                        <div class="d-grid mb-2">
                            <button @click="confirmPayment" :disabled="processing || !stripeReady"
                                    class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                                <span v-if="processing" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="isax isax-lock"></i>
                                {{ processing ? 'Processing payment...' : `Pay ${formatPrice(offer.price, offer.currency)}` }}
                            </button>
                        </div>
                        <p class="text-center text-muted fs-12">
                            <i class="isax isax-lock me-1"></i>Payments processed securely by Stripe.
                        </p>
                        <div class="text-center mt-2">
                            <button @click="step = 'details'" class="btn btn-link btn-sm text-muted">
                                ← Back to passenger details
                            </button>
                        </div>

                    </template>
                </div>

                <!-- ── Right: Order summary ─────────────────────────────── -->
                <div class="col-lg-4 theiaStickySidebar">
                    <div class="card order-details">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between header-content">
                                <h5>Review Order Details</h5>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Airline -->
                            <div class="pb-3 border-bottom mb-3">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="border bg-light rounded p-2 flex-shrink-0"
                                         style="width:52px;height:52px;display:flex;align-items:center;justify-content:center">
                                        <img :src="airlineLogoUrl(offer.airline_iata)" :alt="offer.airline_name"
                                             style="max-width:36px;max-height:36px;object-fit:contain"
                                             @error="($event.target as HTMLImageElement).style.display='none'" />
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ offer.airline_name }}</h6>
                                        <p class="fs-12 text-muted mb-0">
                                            {{ offer.flight_number }} · {{ cabinLabel[offer.cabin_class] ?? offer.cabin_class }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Route timeline -->
                                <div class="bg-light rounded p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-center">
                                            <h5 class="fw-bold mb-0">{{ formatTime(offer.departs_at) }}</h5>
                                            <p class="fs-12 text-muted mb-0 fw-medium">{{ offer.origin }}</p>
                                        </div>
                                        <div class="flex-fill text-center px-2">
                                            <p class="fs-11 text-muted mb-1">{{ parseDuration(offer.duration) }}</p>
                                            <div class="position-relative">
                                                <hr class="my-0">
                                                <span class="position-absolute top-50 start-50 translate-middle bg-light px-1">
                                                    <i class="isax isax-airplane rotate-45 text-primary" style="font-size:13px"></i>
                                                </span>
                                            </div>
                                            <p class="fs-11 text-muted mt-1 mb-0">{{ offer.stop_label }}</p>
                                        </div>
                                        <div class="text-center">
                                            <h5 class="fw-bold mb-0">{{ formatTime(offer.arrives_at) }}</h5>
                                            <p class="fs-12 text-muted mb-0 fw-medium">{{ offer.destination }}</p>
                                        </div>
                                    </div>
                                    <p class="text-center fs-11 text-muted mt-2 mb-0">{{ formatDate(offer.departs_at) }}</p>
                                </div>
                            </div>

                            <!-- Order info -->
                            <div class="mt-3 pb-3 border-bottom">
                                <h6 class="text-primary mb-3">Order Info</h6>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Departure</h6>
                                    <p class="fs-14">{{ formatDate(offer.departs_at) }}</p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Duration</h6>
                                    <p class="fs-14">{{ parseDuration(offer.duration) }}</p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Stops</h6>
                                    <p class="fs-14">{{ offer.stop_label }}</p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Carry-on bag</h6>
                                    <p class="fs-14" :class="offer.baggages.carry_on ? 'text-success' : 'text-muted'">
                                        {{ offer.baggages.carry_on ? '✓ Included' : 'Not included' }}
                                    </p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Checked bag</h6>
                                    <p class="fs-14" :class="offer.baggages.checked ? 'text-success' : 'text-muted'">
                                        {{ offer.baggages.checked ? '✓ Included' : 'Not included' }}
                                    </p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Passengers</h6>
                                    <p class="fs-14">{{ passengerCount }} Adult</p>
                                </div>
                                <div class="d-flex align-items-center details-info">
                                    <h6 class="fs-14">Cabin Class</h6>
                                    <p class="fs-14">{{ cabinLabel[offer.cabin_class] ?? offer.cabin_class }}</p>
                                </div>
                            </div>

                            <!-- Payment info -->
                            <div class="mt-3 pb-3 border-bottom">
                                <h6 class="text-primary mb-3">Payment Info</h6>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fs-14">Total Fare</h6>
                                    <p class="fs-14">{{ formatPrice(offer.price, offer.currency) }}</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fs-14 text-muted">Taxes & fees</h6>
                                    <p class="fs-14 text-muted">Included</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <h6 class="mb-0">Amount to Pay</h6>
                                <h6 class="text-primary mb-0">{{ formatPrice(offer.price, offer.currency) }}</h6>
                            </div>

                            <!-- Trust badges -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex flex-column gap-2 fs-12 text-muted">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="isax isax-shield-security text-primary"></i>
                                        Secure 256-bit SSL encryption
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="isax isax-airplane text-primary"></i>
                                        Instant booking confirmation
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="isax isax-sms text-primary"></i>
                                        E-ticket sent to your email
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->

</template>
