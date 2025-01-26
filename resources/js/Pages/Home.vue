<script setup>
import {Head, Link} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {SquarePen} from "lucide-vue-next";
import PostContent from "@/Components/PostContent.vue";
import InfinitePostLoad from "@/Components/InfinitePostLoad.vue";

const props = defineProps({
    title: String,
    description: String,
    likedColor: String,
    type: String
});

</script>

<template>
    <Head title="Home" />
    <HomeLayout>
        <div class="pb-3 flex items-center justify-between">
            <div>
                <h1 class="font-semibold text-xl dark:text-white">{{ title }}</h1>
                <span class="text-sm text-gray-600">{{ description }}</span>
            </div>
            <Link :href="route('create-post')" type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                <SquarePen class="shrink-0 size-4" />
                New Post
            </Link>
        </div>

        <PostContent :type="props.type" />
    </HomeLayout>
</template>

<style scoped>

</style>
