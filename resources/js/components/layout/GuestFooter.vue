<script setup lang="ts">
import { PlaneTakeoff } from 'lucide-vue-next';
</script>

<template>
    <footer style="background:#111827;color:#9ca3af;" class="px-6 pb-8 pt-12 text-sm">
        <div class="mx-auto max-w-7xl">
            <!-- Top grid -->
            <div class="mb-9 grid gap-8 border-b pb-9 sm:grid-cols-2 lg:grid-cols-4"
                 style="border-color:rgba(255,255,255,0.08)">
                <!-- Brand -->
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg"
                             style="background:linear-gradient(135deg,#1c64f2,#0ea5e9)">
                            <PlaneTakeoff class="h-3.5 w-3.5 text-white" />
                        </div>
                        <span class="font-bold italic text-white">Obiala</span>
                    </div>
                    <p class="text-[0.8rem] leading-relaxed" style="color:#9ca3af">
                        Find the cheapest flights across 500+ airlines. Real-time prices, always sorted lowest first.
                    </p>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="mb-3 text-[0.78rem] font-bold uppercase tracking-wider text-white">Company</h4>
                    <ul class="space-y-2 text-[0.8rem]">
                        <li v-for="l in ['About us','Careers','Press','Blog']" :key="l">
                            <a href="#" class="transition hover:text-white">{{ l }}</a>
                        </li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="mb-3 text-[0.78rem] font-bold uppercase tracking-wider text-white">Support</h4>
                    <ul class="space-y-2 text-[0.8rem]">
                        <li v-for="l in ['Help centre','Contact us','Booking tips','Price alerts']" :key="l">
                            <a href="#" class="transition hover:text-white">{{ l }}</a>
                        </li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="mb-3 text-[0.78rem] font-bold uppercase tracking-wider text-white">Legal</h4>
                    <ul class="space-y-2 text-[0.8rem]">
                        <li v-for="l in ['Privacy policy','Terms of service','Cookie policy','Accessibility']" :key="l">
                            <a href="#" class="transition hover:text-white">{{ l }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom row -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-[0.78rem]">
                <p>© {{ new Date().getFullYear() }} Obiala Travel Ltd. All rights reserved.</p>
                <div class="flex gap-2">
                    <span v-for="b in ['PCI-DSS','SSL Secure','IATA']" :key="b"
                          class="rounded-md border px-2.5 py-1 text-[0.7rem]"
                          style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#9ca3af">
                        {{ b }}
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>
