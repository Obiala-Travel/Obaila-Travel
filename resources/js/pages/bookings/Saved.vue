<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

interface SavedFlight {
    id: number
    origin: string
    destination: string
    depart_at: string | null
    price: number
    currency: string
    flight_data: Record<string, unknown>
    created_at: string
}

defineProps<{ savedFlights: SavedFlight[] }>();

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

function searchAgain(flight: SavedFlight) {
    router.get('/flights/search', {
        origin:      flight.origin,
        destination: flight.destination,
        depart:      flight.depart_at?.split('T')[0] ?? '',
        adults:      1,
        cabin:       'ECONOMY',
        currency:    flight.currency,
        trip_type:   'one-way',
    });
}

function remove(flight: SavedFlight) {
    router.post('/saved-flights', {
        origin:      flight.origin,
        destination: flight.destination,
        depart_at:   flight.depart_at,
        price:       flight.price,
        currency:    flight.currency,
        flight_data: flight.flight_data,
    });
}
</script>

<template>
    <Head title="Saved Flights — Obiala" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-04 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="breadcrumb-title mb-2">Saved Flights</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item active">Saved Flights</li>
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

                    <div class="card hotel-list">
                        <div class="card-body p-0">

                            <!-- List header -->
                            <div class="list-header d-flex align-items-center justify-content-between flex-wrap p-3">
                                <h6 class="mb-0">
                                    {{ savedFlights.length }} saved route{{ savedFlights.length !== 1 ? 's' : '' }}
                                </h6>
                                <Link href="/"
                                      class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                                    <i class="isax isax-add-circle fs-14"></i>
                                    New search
                                </Link>
                            </div>

                            <!-- Empty state -->
                            <div v-if="savedFlights.length === 0" class="text-center py-5">
                                <div class="mb-3">
                                    <span class="avatar avatar-lg bg-danger-subtle rounded-circle d-inline-flex align-items-center justify-content-center">
                                        <i class="isax isax-heart fs-24 text-danger"></i>
                                    </span>
                                </div>
                                <h5 class="mb-2">No saved flights yet</h5>
                                <p class="text-muted fs-14">Tap the heart on any flight to save it for later.</p>
                                <Link href="/" class="btn btn-primary mt-2">Search flights</Link>
                            </div>

                            <!-- Saved flights list -->
                            <div v-else class="p-3 d-flex flex-column gap-3">
                                <div v-for="flight in savedFlights" :key="flight.id"
                                     class="d-flex flex-wrap align-items-center gap-3 p-3 bg-light-200 rounded-2 border">

                                    <!-- Icon -->
                                    <span class="avatar avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="isax isax-airplane text-white fs-16"></i>
                                    </span>

                                    <!-- Route -->
                                    <div class="flex-1">
                                        <p class="fw-semibold mb-0 fs-15">
                                            {{ flight.origin }} → {{ flight.destination }}
                                        </p>
                                        <p class="fs-13 text-muted mb-0">
                                            Departs {{ formatDate(flight.depart_at) }}
                                            · Saved {{ formatDate(flight.created_at) }}
                                        </p>
                                    </div>

                                    <!-- Price -->
                                    <span class="fw-bold fs-16 text-gray-9">
                                        {{ formatPrice(flight.price, flight.currency) }}
                                    </span>

                                    <!-- Actions -->
                                    <div class="d-flex gap-2">
                                        <button @click="searchAgain(flight)"
                                                class="btn btn-primary btn-sm">
                                            Search again
                                        </button>
                                        <button @click="remove(flight)"
                                                class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center"
                                                style="width:36px;height:36px;padding:0"
                                                title="Remove">
                                            <i class="isax isax-heart5 fs-16"></i>
                                        </button>
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
