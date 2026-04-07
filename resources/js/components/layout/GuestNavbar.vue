<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { PlaneTakeoff, Hotel, Menu, X, Heart } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { home, login, register } from '@/routes';

const page       = usePage();
const auth       = computed(() => page.props.auth as { user: any } | null);
const isLoggedIn = computed(() => !!auth.value?.user);
const mobileOpen = ref(false);
</script>

<template>
    <header class="fixed inset-x-0 top-0 z-50 border-b border-gray-100 bg-white text-gray-900 shadow-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">

            <!-- Logo -->
            <Link :href="home().url" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                     style="background: linear-gradient(135deg,#1c64f2,#0ea5e9)">
                    <PlaneTakeoff class="h-4 w-4 text-white" />
                </div>
                <span class="text-lg font-bold italic text-gray-900">Obiala</span>
            </Link>

            <!-- Center nav pills (desktop) -->
            <nav class="hidden items-center gap-1 rounded-xl bg-gray-100 p-1 sm:flex">
                <button class="flex items-center gap-1.5 rounded-lg bg-white px-5 py-1.5 text-sm font-medium text-blue-700 shadow-sm transition">
                    <PlaneTakeoff class="h-3.5 w-3.5" /> Flights
                </button>
                <button class="flex items-center gap-1.5 rounded-lg px-5 py-1.5 text-sm font-medium text-gray-500 transition hover:text-gray-900">
                    <Hotel class="h-3.5 w-3.5" /> Hotels
                </button>
            </nav>

            <!-- Right actions -->
            <div class="flex items-center gap-2">
                <!-- Logged in -->
                <template v-if="isLoggedIn">
                    <button class="hidden rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-red-500 sm:block">
                        <Heart class="h-5 w-5" />
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="size-9 overflow-hidden rounded-full ring-2 ring-blue-100 transition hover:ring-blue-400">
                                <Avatar class="h-full w-full">
                                    <AvatarImage v-if="auth?.user?.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                                    <AvatarFallback class="bg-blue-100 font-semibold text-blue-700">
                                        {{ getInitials(auth?.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth!.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <!-- Logged out -->
                <template v-else>
                    <button class="hidden rounded-full p-2 text-gray-400 transition hover:bg-gray-100 sm:block">
                        <Heart class="h-5 w-5" />
                    </button>
                    <Link :href="login().url"
                          class="hidden rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 sm:block">
                        Log in
                    </Link>
                    <Link :href="register().url"
                          class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                          style="background:#1c64f2">
                        Sign up free
                    </Link>
                </template>

                <!-- Mobile toggle -->
                <button @click="mobileOpen = !mobileOpen"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 sm:hidden">
                    <X v-if="mobileOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Mobile drawer -->
        <div v-if="mobileOpen" class="border-t border-gray-100 bg-white px-4 py-3 sm:hidden">
            <nav class="flex flex-col gap-1">
                <button class="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-600">
                    <PlaneTakeoff class="h-4 w-4" /> Flights
                </button>
                <button class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <Hotel class="h-4 w-4" /> Hotels
                </button>
                <div v-if="!isLoggedIn" class="mt-2 flex flex-col gap-2 border-t border-gray-100 pt-2">
                    <Link :href="login().url">
                        <button class="w-full rounded-lg border border-gray-200 py-2 text-sm font-medium text-gray-700">Log in</button>
                    </Link>
                    <Link :href="register().url">
                        <button class="w-full rounded-lg py-2 text-sm font-semibold text-white" style="background:#1c64f2">Sign up free</button>
                    </Link>
                </div>
            </nav>
        </div>
    </header>
</template>
