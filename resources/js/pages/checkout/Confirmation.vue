<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface Passenger {
    title: string
    first_name: string
    last_name: string
    date_of_birth: string
    gender: string
    passport_number: string
    passport_expiry: string
    nationality: string
}

interface Contact {
    email: string
    phone: string
}

interface Offer {
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
    price: number
    currency: string
}

interface Booking {
    reference: string
    type: string
    origin: string
    destination: string
    depart_at: string
    passengers: number
    cabin_class: string
    total_price: number
    currency: string
    status: string
    offer: Offer
    pax: Passenger[]
    contact: Contact
}

defineProps<{ booking: Booking }>();

function formatTime(iso: string) {
    if (!iso) return '--:--';
    return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
}
function formatDate(iso: string) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
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
</script>

<template>
    <Head :title="`Booking Confirmed — ${booking.reference} — Obiala`" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-05 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="breadcrumb-title mb-2">Flight Booking Confirmation</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item">Flights</li>
                            <li class="breadcrumb-item active">Booking Confirmed</li>
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
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <!-- Success alert -->
                    <div class="alert alert-success d-flex align-items-center gap-3 mb-4 p-4">
                        <span class="avatar avatar-md bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="isax isax-tick-circle text-white fs-20"></i>
                        </span>
                        <div>
                            <h5 class="mb-1">Booking Confirmed!</h5>
                            <p class="mb-0 fs-14">
                                Your e-ticket has been sent to
                                <strong>{{ booking.contact?.email }}</strong>
                            </p>
                        </div>
                        <div class="ms-auto text-end">
                            <p class="fs-12 text-muted mb-1">Booking Reference</p>
                            <h6 class="font-monospace mb-0">{{ booking.reference }}</h6>
                            <span :class="['badge rounded-pill mt-1', booking.status === 'confirmed' ? 'badge-info' : 'badge-warning text-dark']">
                                {{ booking.status === 'confirmed' ? 'Confirmed' : 'Pending' }}
                            </span>
                        </div>
                    </div>

                    <!-- Main confirmation card -->
                    <div class="card booking-confirmation mb-4">
                        <div class="card-body">

                            <!-- Booking header: airline + status -->
                            <div class="bg-light-200 border border-light p-3 rounded-2 mb-4">
                                <div class="d-flex flex-wrap align-items-center justify-content-between">
                                    <div class="d-flex flex-wrap align-items-center booking-hotels">
                                        <div class="avatar avatar-lg me-2 border bg-white rounded-circle overflow-hidden p-1">
                                            <img :src="airlineLogoUrl(booking.offer?.airline_iata ?? '')"
                                                 :alt="booking.offer?.airline_name"
                                                 class="img-fluid rounded-circle"
                                                 style="object-fit:contain"
                                                 @error="($event.target as HTMLImageElement).style.display='none'" />
                                        </div>
                                        <div class="booking-details">
                                            <h6 class="mb-1">{{ booking.offer?.airline_name }}</h6>
                                            <div class="d-flex flex-wrap align-items-center booking-items">
                                                <p class="fs-14 text-gray-6 pe-2 border-end border-light d-flex align-items-center me-2">
                                                    <i class="isax isax-ticket me-1"></i>
                                                    {{ booking.offer?.flight_number }}
                                                </p>
                                                <p class="fs-14 text-gray-6 pe-2 border-end border-light d-flex align-items-center me-2">
                                                    <i class="isax isax-airplane me-1"></i>
                                                    {{ cabinLabel[booking.cabin_class] ?? booking.cabin_class }}
                                                </p>
                                                <p class="fs-14 text-gray-6 d-flex align-items-center">
                                                    <i class="isax isax-people me-1"></i>
                                                    {{ booking.passengers }} passenger{{ booking.passengers > 1 ? 's' : '' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span :class="['badge status rounded-pill p-2 fs-10 d-flex align-items-center', booking.status === 'confirmed' ? 'badge-info' : 'badge-warning text-dark']">
                                            {{ booking.status === 'confirmed' ? 'Confirmed' : 'Pending' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Booking Info grid -->
                            <div class="pb-4 border-bottom mb-4">
                                <h6 class="mb-3">Booking Info</h6>
                                <div class="row g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">From</h6>
                                        <p class="text-gray-6 fs-16">{{ booking.offer?.origin }} <span class="text-muted fs-12">({{ booking.offer?.origin_name }})</span></p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">To</h6>
                                        <p class="text-gray-6 fs-16">{{ booking.offer?.destination }} <span class="text-muted fs-12">({{ booking.offer?.destination_name }})</span></p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Departure</h6>
                                        <p class="text-gray-6 fs-16">{{ formatDate(booking.offer?.departs_at) }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Departure Time</h6>
                                        <p class="text-gray-6 fs-16">{{ formatTime(booking.offer?.departs_at) }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Arrival Time</h6>
                                        <p class="text-gray-6 fs-16">{{ formatTime(booking.offer?.arrives_at) }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Duration</h6>
                                        <p class="text-gray-6 fs-16">{{ parseDuration(booking.offer?.duration) }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Stops</h6>
                                        <p class="text-gray-6 fs-16">{{ booking.offer?.stop_label }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Trip Type</h6>
                                        <p class="text-gray-6 fs-16 text-capitalize">{{ booking.type ?? 'One way' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Passenger Info -->
                            <div class="pb-4 border-bottom mb-4">
                                <h6 class="mb-3">Passenger{{ booking.pax?.length > 1 ? 's' : '' }}</h6>
                                <div class="row g-3">
                                    <div v-for="(pax, i) in booking.pax" :key="i" class="col-lg-6">
                                        <div class="d-flex align-items-center gap-2 p-3 bg-light-200 rounded-2">
                                            <span class="avatar avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                                <i class="isax isax-user text-white fs-14"></i>
                                            </span>
                                            <div>
                                                <p class="fs-14 fw-medium mb-0 text-capitalize">
                                                    {{ pax.title }}. {{ pax.first_name }} {{ pax.last_name }}
                                                    <span class="badge bg-primary ms-1 fs-10">Adult</span>
                                                </p>
                                                <p class="fs-12 text-muted mb-0">
                                                    Passport: {{ pax.passport_number }} · Exp: {{ pax.passport_expiry }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Info -->
                            <div class="pb-4 border-bottom mb-4">
                                <h6 class="mb-3">Billing Info</h6>
                                <div class="row g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Email</h6>
                                        <p class="text-gray-6 fs-16">{{ booking.contact?.email }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Phone</h6>
                                        <p class="text-gray-6 fs-16">{{ booking.contact?.phone }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Info -->
                            <div class="mb-4">
                                <h6 class="mb-3">Order Info</h6>
                                <div class="row g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Booking Reference</h6>
                                        <p class="text-primary fs-16 font-monospace">#{{ booking.reference }}</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Payment Method</h6>
                                        <p class="text-gray-6 fs-16">Stripe</p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Payment Status</h6>
                                        <p :class="['fs-16', booking.status === 'confirmed' ? 'text-success' : 'text-warning']">
                                            {{ booking.status === 'confirmed' ? 'Paid' : 'Pending' }}
                                        </p>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <h6 class="fs-14">Total Paid</h6>
                                        <p class="text-primary fw-bold fs-16">{{ formatPrice(booking.total_price, booking.currency) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- What's next -->
                            <div class="bg-light-200 rounded-2 p-4 mb-4">
                                <h6 class="mb-3">
                                    <i class="isax isax-info-circle text-primary me-2"></i>What happens next?
                                </h6>
                                <ol class="d-flex flex-column gap-2 ps-0 mb-0" style="list-style:none">
                                    <li v-for="(step, i) in [
                                        'Check your inbox — a booking summary has been sent to your email.',
                                        'Payment is processed securely. Your e-ticket will be issued once payment clears.',
                                        'Check in online with the airline 24–48 hours before departure.',
                                    ]" :key="i" class="d-flex align-items-start gap-2">
                                        <span class="avatar avatar-xs bg-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fs-12 text-white fw-bold">
                                            {{ i + 1 }}
                                        </span>
                                        <p class="fs-14 mb-0">{{ step }}</p>
                                    </li>
                                </ol>
                            </div>

                            <!-- Action buttons -->
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <Link href="/"
                                      class="btn btn-light d-inline-flex align-items-center gap-2">
                                    <i class="isax isax-home5"></i>Search more flights
                                </Link>
                                <Link href="/bookings"
                                      class="btn btn-primary d-inline-flex align-items-center gap-2">
                                    View my bookings<i class="isax isax-arrow-right-1"></i>
                                </Link>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->

</template>
