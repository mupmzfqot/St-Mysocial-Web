<script setup>

import { Link } from '@inertiajs/vue3';
import {computed} from "vue";

const props = defineProps({
    links: Array
})

const visibleLinks = computed(() => {
    if (!props.links) return [];

    // Filter out non-numeric labels (like 'Next' or 'Previous')
    const numericLinks = props.links.filter(link => !isNaN(link.label));

    // Limit to first 10 page numbers
    const limitedLinks = numericLinks.slice(0, 5);

    // Add 'Next' and 'Latest' buttons if they exist in original links
    const previousLink = props.links.find(link => link.label === '&laquo; Previous');
    const nextLink = props.links.find(link => link.label === 'Next &raquo;');
    const latestLink = props.links.find(link => link.label === 'Last');
    const moreLink = props.links.find(link => link.label === '...');


    return [
        ...(previousLink ? [previousLink] : []),
        ...limitedLinks,
        ...(moreLink ? [moreLink] : []),
        ...(nextLink ? [nextLink] : []),
        ...(latestLink ? [latestLink] : [])
    ];
})

</script>

<template>
    <nav class="flex items-center -space-x-px" aria-label="Pagination">
        <Link v-for="link in visibleLinks" :href="link.url || ''" :key="link.label"
                :disabled="!link.url"
                :class="{
                'min-h-[38px] min-w-[38px] flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:border-neutral-700 dark:text-white dark:hover:bg-white/10 dark:focus:bg-white/10'
                : !link.active,
                'min-h-[38px] min-w-[38px] flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-none focus:bg-gray-300 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-600 dark:border-neutral-700 dark:text-white dark:focus:bg-neutral-500'
                : link.active
                }"
                aria-current="page">
            <span v-html="link.label"></span>
        </Link>
    </nav>
</template>

<style scoped>

</style>
