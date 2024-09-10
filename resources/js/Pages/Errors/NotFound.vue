<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ChevronLeft } from "lucide-vue-next";
</script>

<template>
    <Head name="Page Not Found" />
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <!-- ========== HEADER ========== -->
        <header class="mb-auto flex justify-center z-50 w-full py-4">
            <nav class="px-4 sm:px-6 lg:px-8">
                <a class="flex-none text-xl font-semibold sm:text-3xl dark:text-white" href="#" aria-label="Brand"></a>
            </nav>
        </header>
        <!-- ========== END HEADER ========== -->

        <!-- ========== MAIN CONTENT ========== -->
        <main id="content">
            <div class="text-center py-10 px-4 sm:px-6 lg:px-8">
                <h1 class="block text-7xl font-bold text-gray-800 sm:text-9xl dark:text-white">404</h1>
                <p class="mt-3 text-gray-600 dark:text-neutral-400">Oops, something went wrong.</p>
                <p class="text-gray-600 dark:text-neutral-400">Sorry, we couldn't find your page.</p>
                <div class="mt-5 flex flex-col justify-center items-center gap-2 sm:flex-row sm:gap-3">
                    <Link :href="route('home')" class="w-full sm:w-auto py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        <ChevronLeft class="shrink-0 size-4" />
                        Back to Homepage
                    </Link>
                </div>
            </div>
        </main>
        <!-- ========== END MAIN CONTENT ========== -->

        <!-- ========== FOOTER ========== -->
        <footer class="mt-auto text-center py-5">
            <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500 dark:text-neutral-500">© All Rights Reserved. 2024.</p>
            </div>
        </footer>
        <!-- ========== END FOOTER ========== -->
    </div>
</template>

<style scoped>

</style>
