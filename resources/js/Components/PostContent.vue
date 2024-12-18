<script setup>
import {Link, router, useForm} from "@inertiajs/vue3";
import {MessageSquareText, Heart, MinusCircle, CheckCircle } from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";

const props = defineProps({
    posts: Object,
    likedColor: String,
    postStatus: false,
});

const form = useForm({
    post_id: ''
});

const sendLike = (id) => {
    form.post_id = id;
    form.post(route('user-post.send-like'), { preserveScroll: false });
}

const showPost = (id) => {
    router.visit(route('user-post.show-post', id));
}

</script>

<template>
    <div v-for="post in posts.data" class="flex flex-col bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="cursor-pointer" @click="showPost(post.id)">
                <Link :href="route('profile.show', post.author.id)" class="flex items-center">
                    <div class="shrink-0">
                        <img class="size-10 rounded-full" :src="post.author.avatar" alt="Avatar">
                    </div>
                    <div class="ms-4">
                        <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ post.author.name }}</div>
                        <div class="text-xs text-gray-500 dark:text-neutral-500">{{ post.created_at }}</div>
                    </div>
                    <span v-if="!post.published && postStatus" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                      <MinusCircle class="size-3" />Not Published
                    </span>
                    <span v-if="post.published && postStatus" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                      <CheckCircle class="size-3" />Published
                    </span>
                </Link>
                <div class="mt-2 text-gray-800 dark:text-neutral-400" v-html="post.post"></div>



                <!-- Image Grid -->
                <PostMedia :medias="post.media" v-if="post.media.length > 0" />
                <!-- End Image Grid -->

                <div class="flex flex-wrap mt-2 gap-x-1" v-if="post.tags">
                    <p class="text-xs text-gray-800 dark:text-gray-200">Tags: </p>
                    <p class="text-xs text-blue-700 italic dark:text-gray-200" v-for="tag in post.tags" :key="tag.id">
                        {{ tag.name }},
                    </p>
                </div>
            </div>

            <div class="inline-flex gap-x-3">
                <Link :href="route('user-post.show-post', post.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <MessageSquareText class="shrink-0 size-4 text-blue-600" />
                    {{ post.comment_count }} Comments

                </Link>
                <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                    <a href="" @click.prevent="sendLike(post.id)">
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="post.is_liked" />
                        <Heart class="shrink-0 size-4 text-gray-500" v-else />
                    </a>
                    {{ post.like_count }} Likes
                </div>
            </div>
        </div>
</template>

<style scoped>

</style>
