<script setup>
import {Head} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";

const props = defineProps({
    media: Object
});

</script>

<template>
    <Head title="Photo Album" />
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">My Photo Albums</h1>
        </div>

        <div class="flex rounded-lg bg-white border shadow-sm p-4">
            <div class="grid grid-cols-2 gap-2">
                <a v-for="image in media" class="group block relative overflow-hidden rounded-lg" href="#">
                    <img class="w-full size-40 object-cover bg-gray-100 rounded-lg dark:bg-neutral-800" :src="image.original_url" alt="Photo album">
                    <div class="absolute bottom-1 end-1 opacity-0 group-hover:opacity-100 transition">
                        <div class="flex items-center gap-x-1 py-1 px-2 bg-white border border-gray-200 text-gray-800 rounded-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                            <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <span class="text-xs">View</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </HomeLayout>
</template>

<style scoped>

</style>
