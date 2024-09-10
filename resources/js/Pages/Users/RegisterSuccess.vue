<script setup>
import { Head, Link } from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";

const { user } = defineProps({
    user: {
        type: Object
    }
})

if(_.isEmpty(user)) {
    window.location.href = route('home');
}

</script>

<template>
    <Head title="Registration success" />
    <GuestLayout>
        <!-- Card Section -->
        <div class="max-w-4xl px-4 py-10 sm:px-6 lg:px-8 mx-auto"><!-- Card -->
            <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-neutral-800">
                <div class="mb-8">
                    <h2 class="text-xl mb-4 font-bold text-gray-800 dark:text-neutral-200">
                        Registration success
                    </h2>
                    <p class="text-sm text-gray-800 dark:text-neutral-400">
                        Welcome, {{ user.name }}!<br>
                        Your account has been successfully created.<br>
                        We’ve sent you an email—please check your inbox to verify and activate your account. We’re excited to have you on board!
                    </p>
                </div>

                <div class="mt-5 flex gap-x-2">
                    <Link :href="route('home')" ype="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Back to home
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
