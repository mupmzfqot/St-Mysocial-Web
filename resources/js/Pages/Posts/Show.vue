<script setup>
import Post from "@/Components/Post.vue";
import {Head, Link} from "@inertiajs/vue3";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ChevronRight} from "lucide-vue-next";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {onMounted, ref} from 'vue';
import axios from 'axios';

const props = defineProps({
    post: {
        type: Object,
    }
});

const loading = ref(false);
const error = ref(null);
const postData = ref(props.post);
const postId = props.post.id;

const reloadPosts = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(route('user-post.get-post', postId));
        postData.value = response.data;
        console.log(response.data)
    } catch (err) {
        error.value = 'Failed to load post.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

// Initialize post data on mount
onMounted(() => {
    reloadPosts();
    console.log(postData.value);
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

        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 w-2/3 inline-block align-middle">
                    <div  class="flex flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
                        <Post :content="postData" @reload-posts="reloadPosts" :status="false" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>

<style scoped>

</style>
