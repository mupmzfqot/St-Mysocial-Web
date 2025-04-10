<script setup>
import {Link, usePage} from '@inertiajs/vue3';

import { onMounted } from 'vue';
const { auth } = usePage().props;

onMounted(() => {
    window.HSStaticMethods.autoInit()
});
</script>

<template>

    <div class="min-h-svh bg-primary-gradient">
        <!-- ========== HEADER ========== -->
        <header class="bg-primary-gradient-reverse flex flex-wrap md:justify-start md:flex-nowrap z-50">
            <nav class="relative max-w-[75vw] w-full md:flex md:items-center md:justify-between mx-auto py-2">
                <!-- Logo w/ Collapse Button -->
                <div class="flex items-center justify-between">
                    <a class="flex-none font-semibold text-sm text-black focus:outline-none focus:opacity-80 dark:text-white" href="#" aria-label="Brand">
                        <img src="../../images/logo.png" alt="Logo" class="lg:h-[2rem] sm:h-8" />
                    </a>

                    <!-- Collapse Button -->
                    <div class="md:hidden">
                        <button type="button" class="hs-collapse-toggle relative size-9 flex justify-center items-center text-sm font-semibold rounded-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-neutral-700 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700" id="hs-header-classic-collapse" aria-expanded="false" aria-controls="hs-header-classic" aria-label="Toggle navigation" data-hs-collapse="#hs-header-classic">
                            <svg class="hs-collapse-open:hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
                            <svg class="hs-collapse-open:block shrink-0 hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            <span class="sr-only">Toggle navigation</span>
                        </button>
                    </div>
                    <!-- End Collapse Button -->
                </div>
                <!-- End Logo w/ Collapse Button -->

                <!-- Collapse -->
                <div v-if="!auth.user" id="hs-header-classic" class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:block" aria-labelledby="hs-header-classic-collapse">
                    <div class="overflow-hidden overflow-y-auto max-h-[75vh]">
                        <div class="py-2 md:py-0 flex flex-col md:flex-row md:items-center md:justify-end gap-0.5 md:gap-1">


                            <Link :href="route('register')" class="p-2 flex font-semibold items-center text-sm text-white hover:text-white focus:outline-none focus:text-white dark:text-neutral-200 dark:hover:text-neutral-500 dark:focus:text-neutral-500" href="#">
                                <svg class="shrink-0 size-4 me-3 md:me-2 block md:hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Register
                            </Link>

                            <!-- Button Group -->
                            <Link :href="route('login')" class="relative flex flex-wrap items-center gap-x-1.5 md:ps-2.5 mt-1 md:mt-0 md:ms-1.5 before:block before:absolute before:top-1/2 before:-start-px before:w-px before:h-4 before:bg-gray-300 before:-translate-y-1/2 dark:before:bg-neutral-700">
                                <a class="p-2 w-full flex items-center font-semibold text-sm text-white hover:text-white focus:outline-none focus:text-gray-500 dark:text-neutral-200 dark:hover:text-neutral-500 dark:focus:text-neutral-500" href="#">
                                    <svg class="shrink-0 size-4 me-3 md:me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Log in
                                </a>
                            </Link>
                            <!-- End Button Group -->
                        </div>
                    </div>
                </div>
                <!-- End Collapse -->
            </nav>
        </header>
        <!-- ========== END HEADER ========== -->
        <div class="flex flex-col sm:justify-center items-center min-h-[90vh]">
            <slot />
        </div>

    </div>
</template>

<style scoped>
.bg-gradient-blue {
    background: rgb(89,27,75);
    background: linear-gradient(90deg, rgba(89,27,75,1) 0%, rgba(46,137,228,1) 100%);
}

.bg-gradient-blue-reverse {
    background: rgb(46,137,228);
    background: linear-gradient(90deg, rgba(46,137,228,1) 0%, rgba(89,27,75,1) 100%);
}
.bg-primary-gradient {
    background: rgb(68,30,167);
    background: -moz-linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    background: -webkit-linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    background: linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#441ea7",endColorstr="#ba3ad9",GradientType=1);
}
.bg-primary-gradient-reverse {
    background: rgb(186,58,217);
    background: -moz-linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    background: -webkit-linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    background: linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#ba3ad9",endColorstr="#441ea7",GradientType=1);
}
.btn-primary {
    background: rgb(68,30,167)
}
</style>
