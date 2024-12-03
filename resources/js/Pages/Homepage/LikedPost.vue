<script setup>
import {Head, Link, useForm, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {MessageSquareText, Heart} from "lucide-vue-next";
import PostContent from "@/Components/PostContent.vue";

const props = defineProps({
    posts: Object,
});

</script>

<template>
    <Head title="Liked Posts" />
    <HomeLayout>

        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">My Likes</h1>
        </div>

        <PostContent :posts="posts" />
    </HomeLayout>
</template>

<style scoped>

</style>
