<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface Booking {
    id: number
    reference: string
    type: 'flight' | 'hotel'
    origin: string | null
    destination: string | null
    depart_at: string | null
    passengers: number
    cabin_class: string | null
    total_price: number
    currency: string
    status: 'pending' | 'confirmed' | 'cancelled'
    created_at: string
    airline: string | null
    flight_no: string | null
}

defineProps<{ bookings: Booking[] }>();

function formatDate(iso: string | null) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency,
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(price);
}

const statusBadge: Record<string, string> = {
    confirmed: 'badge-info',
    pending:   'badge-warning text-dark',
    cancelled: 'badge-danger',
};

const cabinLabel: Record<string, string> = {
    ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Premium Economy',
    BUSINESS: 'Business', FIRST: 'First Class',
};
</script>

<template>
    <Head title="My Bookings — Obiala" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-04 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="breadcrumb-title mb-2">My Bookings</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item active">My Bookings</li>
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

                    <!-- Card -->
                    <div class="card hotel-list">
                        <div class="card-body p-0">

                            <!-- List header -->
                            <div class="list-header d-flex align-items-center justify-content-between flex-wrap p-3">
                                <h6 class="mb-0">
                                    {{ bookings.length }} booking{{ bookings.length !== 1 ? 's' : '' }}
                                </h6>
                                <Link href="/"
                                      class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                                    <i class="isax isax-add-circle fs-14"></i>
                                    New search
                                </Link>
                            </div>

                            <!-- Empty state -->
                            <div v-if="bookings.length === 0" class="text-center py-5">
                                <div class="fs-1 mb-3">✈️</div>
                                <h5 class="mb-2">No bookings yet</h5>
                                <p class="text-muted fs-14">Search for flights and book your first trip.</p>
                                <Link href="/" class="btn btn-primary mt-2">Search flights</Link>
                            </div>

                            <!-- Table -->
                            <div v-else class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Route</th>
                                            <th>Class / Pax</th>
                                            <th>Date</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="booking in bookings" :key="booking.id">
                                            <td>
                                                <Link :href="`/booking/${booking.reference}`"
                                                      class="link-primary fw-medium font-monospace">
                                                    #{{ booking.reference }}
                                                </Link>
                                            </td>
                                            <td>
                                                <span class="d-flex align-items-center gap-1">
                                                    <i :class="['isax fs-16 text-primary',
                                                               booking.type === 'flight' ? 'isax-airplane' : 'isax-building']"></i>
                                                    <span class="fs-14 text-capitalize">{{ booking.type }}</span>
                                                </span>
                                            </td>
                                            <td>
                                                <p class="fw-medium mb-0 fs-14">
                                                    {{ booking.origin ?? '—' }} → {{ booking.destination ?? '—' }}
                                                </p>
                                                <span v-if="booking.airline" class="fs-13 text-gray-6">
                                                    {{ booking.airline }} · {{ booking.flight_no }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="fs-14 mb-0">{{ cabinLabel[booking.cabin_class ?? ''] ?? booking.cabin_class ?? '—' }}</p>
                                                <span class="fs-13 text-gray-6">{{ booking.passengers }} pax</span>
                                            </td>
                                            <td class="fs-14">{{ formatDate(booking.depart_at) }}</td>
                                            <td class="fs-14 fw-semibold">
                                                {{ formatPrice(booking.total_price, booking.currency) }}
                                            </td>
                                            <td>
                                                <span :class="['badge rounded-pill d-inline-flex align-items-center fs-10', statusBadge[booking.status] ?? 'badge-secondary']">
                                                    <i class="fa-solid fa-circle fs-5 me-1"></i>
                                                    {{ booking.status.charAt(0).toUpperCase() + booking.status.slice(1) }}
                                                </span>
                                            </td>
                                            <td>
                                                <Link :href="`/booking/${booking.reference}`"
                                                      class="d-inline-flex align-items-center">
                                                    <i class="isax isax-eye fs-18"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->

</template>
