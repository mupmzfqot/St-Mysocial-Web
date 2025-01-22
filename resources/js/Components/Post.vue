<script setup>

import {CheckCircle, Heart, MessageSquareText, MinusCircle, XCircle, Repeat2} from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import {Link, router} from "@inertiajs/vue3";
import {ref} from "vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import Comment from "@/Components/Comment.vue";

defineProps({
    content: {
        type: Object,
        required: true,
    },
    status: {
        type: Boolean,
        default: false,
    }
});

const likedByUsers = ref([]);
const showLikedByModal = ref(false);
const showDeleteConfirmModal = ref(false);
const postToDelete = ref(false);
const showPostModal = ref(false);
const postDetails = ref(null);

const showPost = (id) => {
    axios.get(route('user-post.show-post', id))
        .then(response => {
            postDetails.value = response.data;
            showPostModal.value = true;
        })
        .catch(error => {
            console.error('Error fetching post details:', error);
        });
};

const sendLike = (id) => {
    router.post(route('user-post.send-like'), { post_id: id }, { preserveScroll: true });
};

const unlike = (id) => {
    router.post(route('user-post.unlike'), { post_id: id }, { preserveScroll: true });
};

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

const showShareModal = ref(false);
const sharePostId = ref(null);
const shareContent = ref('');

const openShareModal = (postId) => {
    sharePostId.value = postId;
    showShareModal.value = true;
};

const submitShare = () => {
    router.post(route('user-post.share'), {
        post_id: sharePostId.value,
        post: shareContent.value
    }, {
        onSuccess: () => {
            showShareModal.value = false;
            shareContent.value = '';
            alert('Post shared successfully!');
        }
    });
};

const refreshComments = () => {
    // If postDetails is already loaded, refresh its comments
    if (postDetails.value) {
        axios.get(route('user-post.show-post', postDetails.value.id))
            .then(response => {
                postDetails.value = response.data;
            })
            .catch(error => {
                console.error('Error refreshing comments:', error);
            });
    } else {
        // If postDetails is not loaded, you might want to reload the entire post
        showPost(content.id);
    }
};

const sendCommentLike = (commentId) => {
    router.post(route('user-post.send-comment-like'), { comment_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally refresh comments after liking
            refreshComments();
        }
    });
};

const unlikeComment = (commentId) => {
    router.post(route('user-post.unlike-comment'), { comment_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally refresh comments after unliking
            refreshComments();
        }
    });
};

const openDeleteCommentConfirm = (commentId) => {
    // You might want to implement a specific delete comment confirmation modal
    router.post(route('user-post.delete-comment'), { content_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            // Refresh comments after deletion
            refreshComments();
        }
    });
};

</script>

<template>
    <div class="cursor-pointer" @click="showPost(content.id)" v-if="content.repost">
        <Link :href="route('profile.show', content.author.id)" class="flex items-center">
            <div class="shrink-0">
                <img class="size-10 rounded-full" :src="content.author.avatar" alt="Avatar">
            </div>
            <div class="ms-4">
                <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ content.author.name }}</div>
                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.created_at }}</div>
            </div>
            <span v-if="!content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                      <MinusCircle class="size-3" />Not Published
                    </span>
            <span v-if="content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                      <CheckCircle class="size-3" />Published
                    </span>
        </Link>

        <div class="mt-2 text-gray-800 text-wrap text-sm dark:text-neutral-400" v-html="content.post"></div>

        <div class="my-3 border border-gray-200 px-3 pb-2 rounded-xl">
            <Link :href="route('profile.show', content.repost.author.id)" class="flex items-center mt-2">
                <div class="shrink-0">
                    <img class="size-10 rounded-full" :src="content.repost.author.avatar" alt="Avatar">
                </div>
                <div class="ms-4">
                    <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ content.repost.author.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.repost.created_at }}</div>
                </div>
            </Link>
            <div class="mt-2 text-gray-800 text-wrap text-sm dark:text-neutral-400" v-html="content.repost.post"></div>

            <!-- Image Grid -->
            <PostMedia :medias="content.repost.media" v-if="content.repost.media.length > 0" />
            <!-- End Image Grid -->

            <div class="flex flex-wrap mt-2 gap-x-1" v-if="content.repost.tags && content.repost.tags.length > 0">
                <p class="text-xs text-gray-800 dark:text-gray-200">Tags: </p>
                <p class="text-xs text-blue-700 italic dark:text-gray-200" v-for="tag in content.repost.tags" :key="tag.id">
                    {{ tag.name }},
                </p>
            </div>
        </div>
    </div>

    <div class="cursor-pointer" @click="showPost(content.id)" v-else>
        <Link :href="route('profile.show', content.author.id)" class="flex items-center">
            <div class="shrink-0">
                <img class="size-10 rounded-full" :src="content.author.avatar" alt="Avatar">
            </div>
            <div class="ms-4">
                <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ content.author.name }}</div>
                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.created_at }}</div>
            </div>
            <span v-if="!content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                      <MinusCircle class="size-3" />Not Published
                    </span>
            <span v-if="content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                      <CheckCircle class="size-3" />Published
                    </span>
        </Link>
        <div class="mt-2 text-gray-800 text-wrap text-sm dark:text-neutral-400" v-html="content.post"></div>

        <!-- Image Grid -->
        <PostMedia :medias="content.media" v-if="content.media.length > 0" />
        <!-- End Image Grid -->

        <div class="flex flex-wrap mt-2 gap-x-1" v-if="content.tags && content.tags.length > 0">
            <p class="text-xs text-gray-800 dark:text-gray-200">Tags: </p>
            <p class="text-xs text-blue-700 italic dark:text-gray-200" v-for="tag in content.tags" :key="tag.id">
                {{ tag.name }},
            </p>
        </div>
    </div>

    <div class="inline-flex gap-x-3">
        <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
            <a href="#" v-if="content.is_liked" @click.prevent="unlike(content.id)">
                <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="content.is_liked" />
            </a>
            <a href="#" @click.prevent="sendLike(content.id)" v-else>
                <Heart class="shrink-0 size-4 text-gray-500" />
            </a>

            <a href="#" @click.prevent="showLikedBy(content.id)" class="hover:underline" v-if="content.like_count !== 0">
                {{ content.like_count }} Likes
            </a>
            <span v-else>
                {{ content.like_count }} Likes
            </span>
        </div>

        <a @click.prevent="showPost(content.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
            <MessageSquareText class="shrink-0 size-4 text-blue-600" />
            {{ content.comment_count }} Comments

        </a>


        <a @click.prevent="openShareModal(content.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
            <Repeat2 class="shrink-0 size-4 text-gray-800" />
            Share
        </a>

        <a href="#" @click.prevent="openDeleteConfirm(content.id)" v-if="$page.props.auth.user.id === content.user_id" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-red-900 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
            <XCircle class="shrink-0 size-4 text-red-700" />Delete post
        </a>
    </div>

    <!-- Delete Confirmation Modal -->
    <TransitionRoot appear :show="showDeleteConfirmModal" as="template" style="position: absolute; z-index: 99999">
        <Dialog as="div" @close="showDeleteConfirmModal = false" class="relative">
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
    <TransitionRoot appear :show="showLikedByModal" as="template" style="position: absolute; z-index: 99999">
        <Dialog as="div" @close="showLikedByModal = false" class="relative">
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

    <!-- Share Modal -->
    <TransitionRoot appear :show="showShareModal" as="template" style="position: absolute; z-index: 9999">
        <Dialog as="div" @close="showShareModal = false" class="relative">
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
                                Share Post
                            </DialogTitle>
                            <div class="mt-2">
                                <textarea
                                    v-model="shareContent"
                                    class="w-full p-2 border rounded-md"
                                    placeholder="Add a comment to your share..."
                                    rows="4"
                                ></textarea>
                                <input
                                    type="hidden"
                                    :value="sharePostId"
                                    name="post_id"
                                />
                            </div>

                            <div class="mt-4">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                    @click="submitShare"
                                >
                                    Share
                                </button>
                                <button
                                    type="button"
                                    class="ml-2 inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                    @click="showShareModal = false"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Post Details Modal -->
    <TransitionRoot appear :show="showPostModal" as="template">
        <Dialog as="div" @close="showPostModal = false" class="relative z-[9999]">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
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
                        <DialogPanel
                            class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-neutral-800 p-6 text-left align-middle shadow-xl transition-all relative max-h-[90vh] flex flex-col"
                        >
                            <!-- Modal Header -->
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900 dark:text-white border-b pb-2 sticky top-0 bg-white dark:bg-neutral-800"
                            >
                                <!-- Post Content -->
                                <div v-if="postDetails" class="space-y-4">
                                    <!-- Post Header -->
                                    <div class="flex items-center space-x-3">
                                        <img
                                            :src="postDetails.author.avatar"
                                            :alt="`${postDetails.author.name}'s avatar`"
                                            class="w-12 h-12 rounded-full"
                                        />
                                        <div>
                                            <h4 class="font-semibold text-gray-800 dark:text-white">
                                                {{ postDetails.author.name }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-neutral-400">
                                                {{ postDetails.created_at }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Post Text -->
                                    <div
                                        class="text-sm text-gray-700 dark:text-neutral-300"
                                        v-html="postDetails.post"
                                    ></div>

                                    <!-- Post Media -->
                                    <PostMedia
                                        v-if="postDetails.media && postDetails.media.length > 0"
                                        :medias="postDetails.media"
                                    />

                                    <div class="my-3 border border-gray-200 px-3 pb-2 rounded-xl" v-if="postDetails.repost">
                                        <Link :href="route('profile.show', postDetails.repost.author.id)" class="flex items-center mt-2">
                                            <div class="shrink-0">
                                                <img class="size-10 rounded-full" :src="postDetails.repost.author.avatar" alt="Avatar">
                                            </div>
                                            <div class="ms-4">
                                                <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ postDetails.repost.author.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ postDetails.repost.created_at }}</div>
                                            </div>
                                        </Link>
                                        <div class="mt-2 text-gray-800 text-wrap text-sm dark:text-neutral-400" v-html="postDetails.repost.post"></div>

                                        <!-- Image Grid -->
                                        <PostMedia :medias="postDetails.repost.media" v-if="postDetails.repost.media.length > 0" />
                                        <!-- End Image Grid -->

                                        <div class="flex flex-wrap mt-2 gap-x-1" v-if="postDetails.repost.tags && postDetails.repost.tags.length > 0">
                                            <p class="text-xs text-gray-800 dark:text-gray-200">Tags: </p>
                                            <p class="text-xs text-blue-700 italic dark:text-gray-200" v-for="tag in postDetails.repost.tags" :key="tag.id">
                                                {{ tag.name }},
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Post Tags -->
                                    <div
                                        v-if="postDetails.tags && postDetails.tags.length > 0"
                                        class="flex flex-wrap gap-2"
                                    >
                                    <span
                                        v-for="tag in postDetails.tags"
                                        :key="tag.id"
                                        class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    </div>

                                    <div class="inline-flex gap-x-3">
                                        <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                                            <a href="#" v-if="content.is_liked" @click.prevent="unlike(content.id)">
                                                <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="content.is_liked" />
                                            </a>
                                            <a href="#" @click.prevent="sendLike(content.id)" v-else>
                                                <Heart class="shrink-0 size-4 text-gray-500" />
                                            </a>

                                            <a href="#" @click.prevent="showLikedBy(content.id)" class="hover:underline" v-if="content.like_count !== 0">
                                                {{ content.like_count }} Likes
                                            </a>
                                            <span v-else>
                                                {{ content.like_count }} Likes
                                            </span>
                                        </div>

                                        <a @click.prevent="showPost(content.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                                            <MessageSquareText class="shrink-0 size-4 text-blue-600" />
                                            {{ content.comment_count }} Comments

                                        </a>


                                        <a @click.prevent="openShareModal(content.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                                            <Repeat2 class="shrink-0 size-4 text-gray-800" />
                                            Share
                                        </a>

                                        <a href="#" @click.prevent="openDeleteConfirm(content.id)" v-if="$page.props.auth.user.id === content.user_id" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-red-900 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                                            <XCircle class="shrink-0 size-4 text-red-700" />Delete post
                                        </a>
                                    </div>
                                </div>
                            </DialogTitle>

                            <!-- Scrollable Content -->
                            <div class="flex-grow overflow-y-auto custom-scrollbar py-2">
                                <!-- Comments Section -->
                                <div class="mt-1">
                                    <p class="text-md font-semibold mb-3 text-gray-800 dark:text-white">
                                        Comments
                                    </p>
                                    <Comment
                                        :post-id="content.id"
                                        :comments="postDetails?.comments || []"
                                        :current-user="$page.props.auth.user"
                                        @like-comment="sendCommentLike"
                                        @unlike-comment="unlikeComment"
                                        @delete-comment="openDeleteCommentConfirm"
                                        @comment-added="refreshComments"
                                    />
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
