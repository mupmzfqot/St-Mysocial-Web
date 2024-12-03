<script setup>
import {Head, Link, router, useForm, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {MessageSquareText, Heart, SquarePen } from "lucide-vue-next";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PostMedia from "@/Components/PostMedia.vue";
import PostContent from "@/Components/PostContent.vue";

const props = defineProps({
    posts: Object,
    title: String,
    description: String,
    likedColor: String
});

const form = useForm({
    post_id: ''
});

const sendLike = (id) => {
    form.post_id = id;
    form.post(route('user-post.send-like'), { preserveScroll: true });
}

const showPost = (id) => {
    router.visit(route('user-post.show-post', id));
}
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

        <PostContent :posts="posts" />
    </HomeLayout>
</template>

<style scoped>

</style>
