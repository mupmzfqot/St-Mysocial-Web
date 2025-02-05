<script setup>
import {Head, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Post from "@/Components/Post.vue";
import Comment from "@/Components/Comment.vue";

const props = defineProps({
    post: Object
});
</script>

<template>
    <Head title="Post Detail" />
    <HomeLayout>
        <div v-if="post" class="relative flex transform flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2">
            <Post :content="post" :singlePost="true" class=" max-h-[80vh]" />
        </div>

        <div v-else class="bg-red-50 border-s-4 border-red-500 p-4 dark:bg-red-800/30" role="alert" tabindex="-1" aria-labelledby="hs-bordered-red-style-label">
            <div class="flex">
                <div class="shrink-0">
                    <!-- Icon -->
                    <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800 dark:border-red-900 dark:bg-red-800 dark:text-red-400">
                          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                          </svg>
                    </span>
                    <!-- End Icon -->
                </div>
                <div class="ms-3">
                    <h3 id="hs-bordered-red-style-label" class="text-gray-800 font-semibold dark:text-white">
                        Info!
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-neutral-400">
                        Post deleted!
                    </p>
                </div>
            </div>
        </div>


    </HomeLayout>
</template>

<style scoped>
</style>
