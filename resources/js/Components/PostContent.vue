<script setup>
import {computed, nextTick, onMounted, onUnmounted, ref} from 'vue';
import Post from "@/Components/Post.vue";
import axios from 'axios';
import {throttle} from 'lodash-es';

const props = defineProps({
    likedColor: String,
    postStatus: {
        type: Boolean,
        default: false
    },
    requestUrl: String
});

const posts = ref([]);
const loading = ref(false);
const error = ref(null);
const page = ref(1);
const hasMorePosts = ref(true);
const retryCount = ref(0);
const MAX_RETRIES = 3;

// check if we can load more posts
const canLoadMore = computed(() => {
    return !loading.value && hasMorePosts.value;
});

const loadMore = async () => {
    if (!canLoadMore.value) return;

    loading.value = true;
    error.value = null;

    try {
        const retryDelay = Math.pow(2, retryCount.value) * 1000;
        const url = new URL(props.requestUrl);
        url.searchParams.set('page', page.value + 1);
        const response = await axios.get(url.toString(), {
            timeout: 1000,
            cancelToken: new axios.CancelToken(c => {
                window.cancelPostRequest = c;
            })
        });

        const newPosts = response.data.data;
        const postContainer = document.querySelector('.post-container');
        if (postContainer) {
            postContainer.classList.add('opacity-50', 'transition-opacity', 'duration-300');

            setTimeout(() => {
                const startIndex = posts.value.length;
                posts.value = [...posts.value, ...newPosts];

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
            posts.value = [...posts.value, ...newPosts];
        }

        retryCount.value = 0;
        page.value = response.data.current_page;
        hasMorePosts.value = !!response.data.next_page_url;
    } catch (err) {
        if (axios.isCancel(err)) {
            return;
        }

        // Retry
        if (retryCount.value < MAX_RETRIES) {
            retryCount.value++;
            error.value = `Loading failed. Retrying (${retryCount.value}/${MAX_RETRIES})...`;
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount.value) * 1000));
            await loadMore();
        } else {
            error.value = 'Failed to load more posts. Please try again later.';
            retryCount.value = 0;
        }
    } finally {
        loading.value = false;
    }
};

const handleScroll = throttle(() => {
    // Log all possible scroll-related properties
    console.log('Comprehensive Scroll Debug:', {
        windowPageYOffset: window.pageYOffset,
        documentElementScrollTop: document.documentElement.scrollTop,
        bodyScrollTop: document.body.scrollTop,
        windowInnerHeight: window.innerHeight,
        documentElementClientHeight: document.documentElement.clientHeight,
        documentElementScrollHeight: document.documentElement.scrollHeight,
        bodyScrollHeight: document.body.scrollHeight,
        loading: loading.value,
        canLoadMore: canLoadMore.value
    });

    // Multiple scroll detection methods
    const scrollTop =
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        document.body.scrollTop ||
        0;

    const windowHeight =
        window.innerHeight ||
        document.documentElement.clientHeight ||
        document.body.clientHeight;

    const documentHeight =
        Math.max(
            document.documentElement.scrollHeight,
            document.body.scrollHeight,
            document.documentElement.offsetHeight,
            document.body.offsetHeight
        );

    // Detailed near bottom logging
    const nearBottom = scrollTop + windowHeight >= documentHeight - 200;

    console.log('Near Bottom Calculation:', {
        scrollTop,
        windowHeight,
        documentHeight,
        calculation: scrollTop + windowHeight,
        threshold: documentHeight - 200,
        isNearBottom: nearBottom
    });

    // Fallback scroll detection
    if (nearBottom && !loading.value && canLoadMore.value) {
        console.log('Attempting to load more posts via scroll...');
        loadMore().catch(err => {
            console.error('Scroll-triggered load error:', err);
        });
    }
}, 300);

onMounted(() => {
    // Multiple scroll event listeners
    window.addEventListener('scroll', handleScroll);
    document.addEventListener('scroll', handleScroll);

    // Intersection Observer as a fallback
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && canLoadMore.value && !loading.value) {
                loadMore().catch(err => {
                    console.error('Intersection Observer load error:', err);
                });
            }
        });
    }, {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    });

    // Optional: Add an observer to the last post if possible
    const lastPost = document.querySelector('.post-container > :last-child');
    if (lastPost) {
        observer.observe(lastPost);
    }

    // Initial posts load
    reloadPosts();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    document.removeEventListener('scroll', handleScroll);
});

const reloadPosts = async () => {
    loading.value = true;
    error.value = null;

    try {
        const url = new URL(props.requestUrl);
        url.searchParams.set('page', page.value);
        const response = await axios.get(url.toString(), {
            timeout: 1000,
        });
        posts.value = response.data.data;
        page.value = response.data.current_page;
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
            :style="`animation-delay: ${index * 10}ms`"
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
