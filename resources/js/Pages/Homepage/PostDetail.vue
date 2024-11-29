<script setup>
import {Head, Link, useForm} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {Heart, MessageSquareText} from "lucide-vue-next";

const props = defineProps({
    post: Object
});

const form = useForm({
    message: '',
    post_id: props.post.id
});

const submitComment = () => {
    form.post(route('user-post.store-comment'), {
        onSuccess: () => {
            form.reset();
        }
    });
};

const sendLike = () => {
    form.post(route('user-post.send-like'), { preserveScroll: true });
}

</script>

<template>
    <Head title="Post Detail" />
    <HomeLayout>
        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-4 md:p-5 mb-3 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
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
                    <a href="" @click.prevent="sendLike">
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="post.is_liked" />
                        <Heart class="shrink-0 size-4 text-gray-500" v-else />
                    </a>
                    {{ post.like_count }} Likes
                </div>
            </div>

            <hr class="mt-3">

            <!-- Comments -->
            <div class="py-6 ps-8">
                <!-- Item -->
                <div class="flex gap-x-3" v-for="comment in post.comments">
                    <!-- Icon -->
                    <div class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:w-px after:-translate-x-[0.5px] after:bg-gray-200 dark:after:bg-neutral-700">
                        <div class="relative z-10 size-7 flex justify-center items-center">
                            <img class="shrink-0 size-7 rounded-full" :src="comment.user.avatar" alt="Avatar">
                        </div>
                    </div>
                    <!-- End Icon -->

                    <!-- Right Content -->
                    <div class="grow pt-0.5 pb-8">
                        <h3 class="flex gap-x-1.5 font-semibold text-gray-800 dark:text-white">
                            {{ comment.user.name }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
                            {{ comment.message }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
                            {{ comment.created_at }}
                        </p>
                    </div>
                    <!-- End Right Content -->
                </div>
                <!-- End Item -->

                <!-- Item -->
                <div class="flex gap-x-3">
                    <!-- Icon -->
                    <div class="relative ">
                        <div class="relative z-10 size-7 flex justify-center items-center">
                            <img class="shrink-0 size-7 rounded-full" :src="$page.props.auth.user.avatar" alt="Avatar">
                        </div>
                    </div>
                    <!-- End Icon -->

                    <!-- Right Content -->
                    <div class="grow pt-0.5 pb-8">
                        <form @submit.prevent="submitComment">
                            <label for="textarea-label" class="block text-sm font-medium mb-2 dark:text-white">Comment</label>
                            <textarea id="textarea-label" v-model="form.message" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" rows="3" placeholder="Say hi..."></textarea>
                            <button type="submit" class="py-2 mt-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                Send Comment
                            </button>
                        </form>
                    </div>
                    <!-- End Right Content -->
                </div>
                <!-- End Item -->

            </div>
            <!-- End Comments -->
        </div>


    </HomeLayout>
</template>

<style scoped>

</style>
