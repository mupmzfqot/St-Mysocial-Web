<script setup>
import Post from "@/Components/Post.vue";
import {Head, Link} from "@inertiajs/vue3";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ChevronRight} from "lucide-vue-next";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {onMounted, ref, computed} from 'vue';
import axios from 'axios';

const props = defineProps({
    post: {
        type: Object,
        required: true
    }
});

const loading = ref(false);
const error = ref(null);
const postData = ref(props.post || null);

// Computed property untuk postId dengan validasi
const postId = computed(() => {
    return props.post?.id || postData.value?.id;
});

const reloadPosts = async () => {
    if (!postId.value) {
        error.value = 'Post ID not found.';
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(route('user-post.get-post', postId.value));
        postData.value = response.data;
        console.log(response.data);
    } catch (err) {
        error.value = 'Failed to load post.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

// Initialize post data on mount
onMounted(() => {
    if (postId.value) {
        reloadPosts();
    }
    console.log('Post data:', postData.value);
});
</script>

<template>
    <Head title="Post Detail" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Post Detail
            </li>
        </Breadcrumbs>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border-s-4 border-red-500 p-4 dark:bg-red-800/30" role="alert">
            <div class="flex">
                <div class="shrink-0">
                    <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800 dark:border-red-900 dark:bg-red-800 dark:text-red-400">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </span>
                </div>
                <div class="ms-3">
                    <h3 class="text-gray-800 font-semibold dark:text-white">Error!</h3>
                    <p class="text-sm text-gray-700 dark:text-neutral-400">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Post Content -->
        <div v-else-if="postData" class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 w-2/3 inline-block align-middle">
                    <div class="flex flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
                        <Post :content="postData" @reload-posts="reloadPosts" :status="false" />
                    </div>
                </div>
            </div>
        </div>

        <!-- No Post Data -->
        <div v-else class="bg-yellow-50 border-s-4 border-yellow-500 p-4 dark:bg-yellow-800/30" role="alert">
            <div class="flex">
                <div class="shrink-0">
                    <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-yellow-100 bg-yellow-200 text-yellow-800 dark:border-yellow-900 dark:bg-yellow-800 dark:text-yellow-400">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </span>
                </div>
                <div class="ms-3">
                    <h3 class="text-gray-800 font-semibold dark:text-white">No Post Data</h3>
                    <p class="text-sm text-gray-700 dark:text-neutral-400">Post data is not available.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
