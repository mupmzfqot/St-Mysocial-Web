<script setup>
import {Head} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import PostContent from "@/Components/PostContent.vue";

const props = defineProps({
    posts: Object,
});

</script>

<template>
    <Head title="Top Posts" />
    <HomeLayout>

        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Top Posts</h1>
        </div>

        <PostContent :posts="posts" />
    </HomeLayout>
</template>

<style scoped>

</style>
