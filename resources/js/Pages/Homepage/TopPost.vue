<script setup>
import {Head, Link, useForm, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {MessageSquareText, Heart} from "lucide-vue-next";

const props = defineProps({
    posts: Object,
    likedColor: String
});

const form = useForm({
    post_id: ''
});

const sendLike = (id) => {
    form.post_id = id;
    form.post(route('user-post.send-like'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Liked Posts" />
    <HomeLayout>

        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Top Posts</h1>
        </div>

        <div v-for="post in posts" class="flex flex-col bg-white border shadow-sm rounded-xl p-4 md:p-5 mb-3 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex items-center">
                <div class="shrink-0">
                    <img class="size-10 rounded-full" :src="post.author.avatar" alt="Avatar">
                </div>
                <div class="ms-4">
                    <div class="text-base font-semibold text-gray-800 dark:text-neutral-400">{{ post.author.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-neutral-500">{{ post.created_at }}</div>
                </div>
            </div>
            <div class="mt-2 text-gray-500 dark:text-neutral-400" v-html="post.post"></div>

            <!-- Image Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-1 gap-2 py-2" v-if="post.media.length > 0">
                <a class="group block relative overflow-hidden rounded-lg" href="#" v-for="media in post.media">
                    <div v-if="media.mime_type !== 'video/mp4'">
                        <img class="w-full object-cover bg-gray-100 rounded-lg dark:bg-neutral-800" :src="media.preview_url" alt="Project">
                        <div class="absolute bottom-1 end-1 opacity-0 group-hover:opacity-100 transition">
                            <div class="flex items-center gap-x-1 py-1 px-2 bg-white border border-gray-200 text-gray-800 rounded-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                                <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <span class="text-xs">View</span>
                            </div>
                        </div>
                    </div>

                    <video v-else-if="media.mime_type.startsWith('video/')" controls class="aspect-video rounded-lg">
                        <source :src="media.original_url" :type="media.mime_type" />
                        Your browser does not support the video tag.
                    </video>
                </a>

            </div>
            <!-- End Image Grid -->

            <div class="inline-flex gap-x-3">
                <Link :href="route('user-post.show-post', post.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <MessageSquareText class="shrink-0 size-4 text-blue-600" />
                    {{ post.comment_count }} Comments

                </Link>
                <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <a href="" @click.prevent="sendLike(post.id)">
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="post.is_liked" />
                        <Heart class="shrink-0 size-4 text-gray-500" v-else />
                    </a>
                    {{ post.like_count }} Likes
                </div>
            </div>
        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
