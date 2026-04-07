<script setup lang="ts">
import GuestNavbar from '@/components/layout/GuestNavbar.vue';
import GuestFooter from '@/components/layout/GuestFooter.vue';
</script>

<template>
    <GuestNavbar />

    <!-- Page content — always light mode, padded below fixed navbar -->
    <main class="min-h-screen bg-white pt-16 text-gray-900">
        <slot />
    </main>

    <GuestFooter />
</template>
