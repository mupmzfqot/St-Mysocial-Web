<script setup>
import { onMounted, onUnmounted, ref, computed, nextTick } from 'vue';
import Post from "@/Components/Post.vue";
import axios from 'axios';
import { throttle } from 'lodash-es';

const props = defineProps({
    posts: {
        type: Object,
        required: true
    },
    likedColor: String,
    postStatus: {
        type: Boolean,
        default: false
    },
});

const posts = ref(props.posts.data || []);
const loading = ref(false);
const error = ref(null);
const page = ref(props.posts.current_page);
const hasMorePosts = ref(!!props.posts.next_page_url);
const retryCount = ref(0);
const MAX_RETRIES = 3;

// check if we can load more posts
const canLoadMore = computed(() => !loading.value && hasMorePosts.value);

const loadMore = async () => {
    if (!canLoadMore.value) return;

    loading.value = true;
    error.value = null;


    try {
        const retryDelay = Math.pow(2, retryCount.value) * 1000;
        const response = await axios.get(`user-post/get?page=${page.value + 1}`, {
            timeout: 10000,
            cancelToken: new axios.CancelToken(c => {
                window.cancelPostRequest = c;
            })
        });

        const newPosts = response.data.data;
        // transition with animation
        const postContainer = document.querySelector('.post-container');
        if (postContainer) {
            postContainer.classList.add('opacity-50', 'transition-opacity', 'duration-300');

            setTimeout(() => {
                // Append new posts with staggered animation
                const startIndex = posts.value.length;
                posts.value = [...posts.value, ...newPosts];

                // Animate new posts
                nextTick(() => {
                    const newPostElements = document.querySelectorAll('.post-container > div');
                    newPostElements.forEach((el, index) => {
                        if (index >= startIndex) {
                            el.classList.add('animate-fade-in');
                        }
                    });
                });

                postContainer.classList.remove('opacity-50');
            }, 300);
        } else {
            // Fallback if no container found
            posts.value = [...posts.value, ...newPosts];
        }

        // Reset retry count on successful load
        retryCount.value = 0;

        // Update pagination info
        page.value = response.data.current_page;
        hasMorePosts.value = !!response.data.next_page_url;
    } catch (err) {
        // Handle cancellation separately
        if (axios.isCancel(err)) {
            console.log('Request canceled', err.message);
            return;
        }

        // Retry mechanism
        if (retryCount.value < MAX_RETRIES) {
            retryCount.value++;
            error.value = `Loading failed. Retrying (${retryCount.value}/${MAX_RETRIES})...`;

            // Wait before retry
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount.value) * 1000));

            // Recursive retry
            loadMore();
        } else {
            error.value = 'Failed to load more posts. Please try again later.';
            console.error(err);
            retryCount.value = 0;
        }
    } finally {
        loading.value = false;
    }
};

const handleScroll = throttle(() => {
    // More precise scroll detection
    const bottomOfWindow = document.documentElement.scrollTop + window.innerHeight >=
        document.documentElement.offsetHeight - 200; // 200px before bottom

    if (bottomOfWindow && canLoadMore.value) {
        loadMore();
    }
}, 300); // 300ms throttle time

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (window.cancelPostRequest) {
        window.cancelPostRequest();
    }
});

const reloadPosts = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`user-post/get?page=${page.value}`, {
            timeout: 10000
        });
        posts.value = response.data.data;
        hasMorePosts.value = !!response.data.next_page_url;
        loading.value = false;
    } catch (err) {
        loading.value = false;
    }
};
</script>

<template>
    <div class="post-container flex flex-col">
        <!-- Progressive Content Loading with Placeholder -->
        <template v-if="posts.length === 0 && loading">
            <div
                v-for="n in 5"
                :key="n"
                class="bg-gray-100 dark:bg-neutral-800 h-40 mb-4 rounded-xl animate-pulse"
            >
                <div class="p-4 space-y-4">
                    <div class="h-4 bg-gray-200 dark:bg-neutral-700 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-neutral-700 rounded w-1/2"></div>
                </div>
            </div>
        </template>

        <!-- Actual Posts -->
        <div
            v-for="(post, index) in posts"
            :key="post.id"
            class="flex flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70 transition-all duration-300 ease-in-out opacity-0 transform translate-y-10 animate-fade-in"
            :style="`animation-delay: ${index * 50}ms`"
        >
            <Post @reload-posts="reloadPosts" :content="post" :status="postStatus"/>
        </div>

        <!-- Retry and Error Handling -->
        <div
            v-if="error"
            class="text-red-500 text-center my-4 bg-red-50 p-4 rounded-xl animate-bounce"
        >
            <p>{{ error }}</p>
            <button
                v-if="retryCount >= MAX_RETRIES"
                @click="loadMore"
                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            >
                Retry Loading
            </button>
        </div>

        <!-- End of Posts Indicator -->
        <div
            v-if="!loading && !hasMorePosts"
            class="text-center text-gray-500 my-4 animate-fade-in"
        >
            No more posts to load
        </div>

        <!-- Debug Info -->
        <div class="text-sm text-gray-500 text-center my-2">
            Current Page: {{ page }} | Posts loaded: {{ posts.length }}
            <span v-if="retryCount > 0" class="ml-2 text-yellow-600">
                Retry Attempts: {{ retryCount }}
            </span>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}
</style>
