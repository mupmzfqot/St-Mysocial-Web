<script setup>
import { Head, Link } from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";

defineProps({
    message: ''
})

</script>

<template>
    <Head title="Registration success" />
    <GuestLayout>
        <!-- Card Section -->
        <div class="max-w-4xl px-4 py-10 sm:px-6 lg:px-8 mx-auto"><!-- Card -->
            <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-neutral-800">
                <div class="mb-8">
                    <h2 class="text-xl mb-4 font-bold text-gray-800 dark:text-neutral-200">
                        Registration succeeded
                    </h2>
                    <div v-if="!message">
                        <p class="text-sm text-gray-800 dark:text-neutral-400">
                            Before you can login, your account must be manually by an administrator.
                        </p>
                        <p>Please wait, this can take some time.</p>
                    </div>
                    <p class="text-sm text-gray-800 dark:text-neutral-400">{{ message }}</p>
                </div>

                <div class="mt-5 flex gap-x-2">
                    <Link :href="route('login')" ype="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Back to login
                    </Link>
                </div>
            </div>
            <!-- End Card -->
        </div>
        <!-- End Card Section -->
    </GuestLayout>
</template>

<style scoped>

</style>
