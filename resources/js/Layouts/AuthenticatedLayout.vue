<script setup>

import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import { MenuIcon } from "lucide-vue-next";

import { onMounted } from 'vue';
import {HSCopyMarkup as HSStaticMethods} from "preline";

onMounted(() => {
    window.HSStaticMethods.autoInit()
});

</script>

<template>

    <Navbar />

    <!-- ========== MAIN CONTENT ========== -->
    <div class="-mt-px">
        <!-- Breadcrumb -->
        <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
            <div class="flex items-center py-2">
                <!-- Navigation Toggle -->
                <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
                    <MenuIcon />
                    <span class="sr-only">Toggle Navigation</span>
                </button>
                <!-- End Navigation Toggle -->

                <!-- Breadcrumb -->
                <ol class="ms-3 flex items-center whitespace-nowrap">
                    <li class="flex items-center text-sm text-gray-800">
                        Home
                        <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </li>
                    <li class="text-sm font-semibold text-gray-800 truncate" aria-current="page">
                        Dashboard
                    </li>
                </ol>
                <!-- End Breadcrumb -->
            </div>
        </div>
        <!-- End Breadcrumb -->
    </div>

    <Sidebar />

    <!-- Content -->
    <div class="w-full lg:ps-64 dark:bg-neutral-800">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 ">
            <!-- your content goes here ... -->
            <slot />
        </div>
    </div>
    <!-- End Content -->

    <FlashMessage
        v-if="$page.props.flash.success"
        :isVisible="true"
        :message="$page.props.flash.success"
    />

    <!-- ========== END MAIN CONTENT ========== -->
</template>

<style scoped>
.bg-gradient-blue {
    background: rgb(181,95,200);
    background: linear-gradient(90deg, rgba(181,95,200,1) 0%, rgba(0,31,241,1) 100%);
}
.bg-gradient-blue-reverse {
    background: rgb(0,31,241);
    background: linear-gradient(90deg, rgba(0,31,241,1) 0%, rgba(181,95,200,1) 100%);
}
</style>
