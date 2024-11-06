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
        <div>
            <p class="relative pb-4 text-lg font-bold text-gray-800 dark:text-white focus:outline-none">
                My Photo Album
            </p>
        </div>
        <div class="py-2">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <img v-for="image in media" class="w-full object-cover" :src="image.original_url" alt="Gallery Image">
           </div>
        </div>
    </HomeLayout>
</template>

<style scoped>

</style>
