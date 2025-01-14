<script setup>
import {Link, router} from "@inertiajs/vue3";
import {MessageSquareText, Heart, MinusCircle, CheckCircle, XCircle } from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import { onMounted, onUnmounted, ref, watch } from 'vue';
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";

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

const loading = ref(false);
const posts = ref(props.posts.data || []);
const showDeleteConfirmModal = ref(false);
const postToDelete = ref(false);

const openDeleteConfirm = (id) => {
    postToDelete.value = id;
    showDeleteConfirmModal.value = true;
};

const deletePost = () => {
    if (postToDelete.value) {
        router.post(route('user-post.delete'), { content_id: postToDelete.value }, { preserveScroll: true });
        showDeleteConfirmModal.value = false;
        postToDelete.value = null;
    }
};

// Watch for changes in props.posts and update local posts
watch(() => props.posts.data, (newPosts) => {
    if (newPosts) {
        posts.value = newPosts;
    }
}, { deep: true });

const handleScroll = () => {
    if (loading.value) return;

    const scrollPosition = window.scrollY;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;

    if (scrollPosition + windowHeight >= documentHeight * 0.8) {
        loadMore();
    }
};

const loadMore = () => {
    if (loading.value || !props.posts.next_page_url) return;

    loading.value = true;

    const nextPage = props.posts.current_page + 1;
    router.get(
        window.location.pathname,
        { page: nextPage },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                loading.value = false;
            },
            onError: (error) => {
                loading.value = false;
            }
        }
    );
};

const sendLike = (id) => {
    router.post(route('user-post.send-like'), { post_id: id }, { preserveScroll: true });
};

const unlike = (id) => {
    router.post(route('user-post.unlike'), { post_id: id }, { preserveScroll: true });
};

const showPost = (id) => {
    router.visit(route('user-post.show-post', id));
};

const likedByUsers = ref([]);
const showLikedByModal = ref(false);

const showLikedBy = (id) => {
    axios.get(route('user-post.liked-by', id))
        .then(response => {
            likedByUsers.value = response.data;
            showLikedByModal.value = true;
        })
        .catch(error => {
            console.error('Error fetching liked by users:', error);
        })
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="flex flex-col">
        <div v-for="post in posts" :key="post.id" class="flex flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
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

                <div class="flex flex-wrap mt-2 gap-x-1" v-if="post.tags && post.tags.length > 0">
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
                    <a href="#" v-if="post.is_liked" @click.prevent="unlike(post.id)">
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="post.is_liked" />
                    </a>
                    <a href="#" @click.prevent="sendLike(post.id)" v-else>
                        <Heart class="shrink-0 size-4 text-gray-500" />
                    </a>

                    <a href="#" @click.prevent="showLikedBy(post.id)" class="hover:underline" v-if="post.like_count !== 0">
                        {{ post.like_count }} Likes
                    </a>
                    <span v-else>
                        {{ post.like_count }} Likes
                    </span>
                </div>

                <a href="#" @click.prevent="openDeleteConfirm(post.id)" v-if="$page.props.auth.user.id === post.user_id" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-red-900 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                    <XCircle class="shrink-0 size-4 text-red-700" />Delete post
                </a>
            </div>
        </div>

        <!-- Loading indicator -->
        <div v-if="loading" class="flex justify-center items-center my-4">
            <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 border-t-blue-600 animate-spin"></div>
        </div>

        <!-- Debug info -->
        <div class="text-sm text-gray-500 text-center my-2">
            Current Page: {{ props.posts.current_page }} / Total Pages: {{ props.posts.last_page }} | Posts loaded: {{ posts.length }}
        </div>

        <!-- End message -->
        <div v-if="!loading && !props.posts.next_page_url" class="text-center text-gray-500 my-4">
            No more posts to load
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <TransitionRoot appear :show="showDeleteConfirmModal" as="template">
        <Dialog as="div" @close="showDeleteConfirmModal = false" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                Delete Post
                            </DialogTitle>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete this post? This action cannot be undone.
                                </p>
                            </div>

                            <div class="mt-4 flex justify-end space-x-2">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                    @click="showDeleteConfirmModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                    @click="deletePost"
                                >
                                    Delete
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Liked By Modal -->
    <TransitionRoot appear :show="showLikedByModal" as="template">
        <Dialog as="div" @close="showLikedByModal = false" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-neutral-800">
                            <DialogTitle as="h3" class="text-md font-medium leading-6 text-gray-900 dark:text-white mb-4">
                                People who like this
                            </DialogTitle>
                            <div class="mt-4 max-h-[400px] overflow-y-auto">
                                <div v-if="likedByUsers.length === 0" class="text-center text-gray-500 dark:text-neutral-400">
                                    No likes yet
                                </div>
                                <div v-else class="grid grid-cols-2 gap-3">
                                    <Link
                                        v-for="user in likedByUsers"
                                        :key="user.id"
                                        :href="route('profile.show', user.id)"
                                        class="flex items-center hover:bg-gray-100 dark:hover:bg-neutral-700 px-2 py-1 rounded-lg"
                                    >
                                        <img
                                            :src="user.avatar"
                                            :alt="user.name"
                                            class="w-10 h-10 rounded-full mr-3"
                                        />
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800 dark:text-neutral-300">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-neutral-500">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                    @click="showLikedByModal = false"
                                >
                                    Close
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>
.text-gray-800 :deep(a) {
    color: #3b82f6; /* Tailwind blue-500 */
    text-decoration: none;
}

.text-gray-800 :deep(a:hover) {
    color: #2563eb; /* Tailwind blue-600 */
    text-decoration: underline;
}

</style>
