<script setup>

import {CheckCircle, Heart, MessageSquareText, MinusCircle, XCircle, Repeat2} from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import Comment from "@/Components/Comment.vue";

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    status: {
        type: Boolean,
        default: false,
    },
    singlePost: {
        type: Boolean,
        default: false,
    }
});

const likedByUsers = ref([]);
const taggedUsers = ref([]);
const showLikedByModal = ref(false);
const showDeleteConfirmModal = ref(false);
const postToDelete = ref(false);
const showPostModal = ref(false);
const showTaggedModal = ref(false);
const postDetails = ref(null);

const showPost = (id) => {
    if(props.singlePost === true) {
        showPostModal.value = false;
    } else {
        axios.get(route('user-post.get-post', id))
            .then(response => {
                postDetails.value = response.data;
                showPostModal.value = true;
            });
    }
};

const sendLike = (id) => {
    axios.post(route('user-post.send-like'), { post_id: id })
        .then(response => {
            emit('reload-posts');
        });
};

const unlike = (id) => {
    axios.post(route('user-post.unlike'), { post_id: id })
        .then(response => {
            emit('reload-posts');
        });
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

const showTaggedUser = (id) => {
    axios.get(route('user-post.tagged-user', id))
        .then(response => {
            taggedUsers.value = response.data;
            console.log(taggedUsers);
            showTaggedModal.value = true;
        })
        .catch(error => {
            console.error('Error fetching tagged users:', error);
        })
};

const openDeleteConfirm = (id) => {
    postToDelete.value = id;
    showDeleteConfirmModal.value = true;
};

const deletePost = () => {
    if (postToDelete.value) {
        axios.post(route('user-post.delete'), { content_id: postToDelete.value })
            .then(response => {
                emit('reload-posts');
            });
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

const emit = defineEmits(['reload-posts']);
const submitShare = () => {
    axios.post(route('user-post.share'), {
        post_id: sharePostId.value,
        post: shareContent.value
    }).then(() => {
        showShareModal.value = false;
        shareContent.value = '';
        emit('reload-posts');
    })
};

const refreshComments = () => {
    if (postDetails.value) {
        axios.get(route('user-post.get-post', postDetails.value.id))
            .then(response => {
                postDetails.value = response.data;
                emit('reload-posts');
            })
    } else {
        showPost(content.id);
    }
};

const sendCommentLike = (commentId) => {
    router.post(route('user-post.send-comment-like'), { comment_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            refreshComments();
        }
    });
};

const unlikeComment = (commentId) => {
    router.post(route('user-post.unlike-comment'), { comment_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            refreshComments();
        }
    });
};

const openDeleteCommentConfirm = (commentId) => {
    router.post(route('user-post.delete-comment'), { content_id: commentId }, {
        preserveScroll: true,
        onSuccess: () => {
            refreshComments();
        }
    });
};

const formatTags = (tags) => {
    if (!tags || tags.length === 0) return '';
    if (tags.length <= 2) return tags.join(', ');

    const firstTwoTags = tags.slice(0, 2);
    const remainingCount = tags.length - 2;

    return `${firstTwoTags.join(', ')}, and ${remainingCount} other${remainingCount > 1 ? 's' : ''}`;
}
const styledTag = (value) => {
    return value.replace(/<a /g, '<a class="text-blue-600 hover:text-blue-800 hover:no-underline" ')
        .replace(/<ul>/g, '<ul class="list-disc list-inside pl-4">')
        .replace(/<ol>/g, '<ol class="list-decimal list-inside pl-3.5">');
}

const handleLinkClick = (event) => {
    const target = event.target.closest('a');
    if (target) {
        event.preventDefault();
        event.stopPropagation();

        let href = target.getAttribute('href');
        if (href) {
            href = href.trim();

            let finalUrl;
            if (!href.startsWith('http://') && !href.startsWith('https://')) {
                if (href.includes('.')) {
                    finalUrl = `https://${href}`;
                } else {
                    finalUrl = href;
                }
            } else {
                finalUrl = href;
            }

            window.open(finalUrl, '_blank', 'noopener,noreferrer');
        }
        return false;
    }
};
</script>

<template>
    <div v-if="content.repost">
        <div class="flex items-center">
            <Link :href="route('profile.show', content.author.id)" class="shrink-0">
                <img class="size-10 rounded-full" :src="content.author.avatar" alt="Avatar">
            </Link>
            <div class="ms-4">
                <div class="flex items-center">
                    <Link :href="route('profile.show', content.author.id)" class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700 me-1">{{ content.author.name }}</Link>
                    <div class="flex flex-wrap gap-x-1" v-if="content.tags && content.tags.length > 0">
                        <p class="text-sm text-gray-800 dark:text-gray-200">with </p>
                        <p @click.stop="showTaggedUser(content.id)" class="text-sm text-blue-700 dark:text-gray-200">
                            {{ formatTags(content.tags.map(tag => tag.name)) }}
                        </p>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.created_at }}</div>
            </div>
            <span v-if="!content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                      <MinusCircle class="size-3" />Not Published
                    </span>
            <span v-if="content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                      <CheckCircle class="size-3" />Published
                    </span>
        </div>

        <div class="mt-2 text-gray-800 text-wrap text-justify text-sm dark:text-neutral-400" v-html="styledTag(content.post)"></div>

        <div class="my-3 border border-gray-200 px-3 pb-2 rounded-xl">
            <div class="flex items-center mt-2">
                <Link :href="route('profile.show', content.repost.author.id)" class="shrink-0">
                    <img class="size-10 rounded-full" :src="content.repost.author.avatar" alt="Avatar">
                </Link>
                <div class="ms-4">
                    <div class="flex items-center">
                        <Link :href="route('profile.show', content.repost.author.id)" class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700 me-1">{{ content.repost.author.name }}</Link>
                        <div class="flex flex-wrap gap-x-1" v-if="content.repost.tags && content.repost.tags.length > 0">
                            <p class="text-sm text-gray-800 dark:text-gray-200">with </p>
                            <p @click.stop="showTaggedUser(content.id)" class="text-sm text-blue-700 dark:text-gray-200">
                                {{ formatTags(content.repost.tags.map(tag => tag.name)) }}
                            </p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.repost.created_at }}</div>
                </div>
            </div>
            <div @click="handleLinkClick" class="mt-2 text-gray-800 text-wrap text-justify text-sm dark:text-neutral-400" v-html="styledTag(content.repost.post)"></div>

            <!-- Image Grid -->
            <PostMedia :medias="content.repost.media" v-if="content.repost.media.length > 0" />
            <!-- End Image Grid -->
        </div>
    </div>

    <!-- if repost empty -->
    <div v-else>
        <div class="flex items-center">
            <Link :href="route('profile.show', content.author.id)" class="shrink-0">
                <img class="size-10 rounded-full" :src="content.author.avatar" alt="Avatar">
            </Link>
            <div class="ms-4">
                <div class="flex items-center">
                    <Link :href="route('profile.show', content.author.id)" class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700 me-1">{{ content.author.name }}</Link>
                    <div class="flex flex-wrap gap-x-1" v-if="content.tags && content.tags.length > 0">
                        <p class="text-sm text-gray-800 dark:text-gray-200">with </p>
                        <p @click.stop="showTaggedUser(content.id)" class="text-sm text-blue-700 dark:text-gray-200">
                            {{ formatTags(content.tags.map(tag => tag.name)) }}
                        </p>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ content.created_at }}</div>
            </div>
            <span v-if="!content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                      <MinusCircle class="size-3" />Not Published
                    </span>
            <span v-if="content.published && status" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                      <CheckCircle class="size-3" />Published
                    </span>
        </div>
        <div @click="handleLinkClick" class="mt-2 text-gray-800 text-wrap text-justify text-sm dark:text-neutral-400" v-html="styledTag(content.post)"></div>

        <!-- Image Grid -->
        <PostMedia :medias="content.media" v-if="content.media.length > 0" />
        <!-- End Image Grid -->
    </div>

    <hr class="my-3 border-1 -mx-4">
    <div class="inline-flex items-center justify-between">
        <div class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
            <a href="#" v-if="content.is_liked" @click.prevent="unlike(content.id)">
                <Heart class="shrink-0 size-5 fill-red-500 text-transparent" v-if="content.is_liked" />
            </a>
            <a href="#" class="inline-flex gap-x-2" @click.prevent="sendLike(content.id)" v-else>
                <Heart class="shrink-0 size-5 text-gray-800" />
            </a>

            <a href="#" @click.prevent="showLikedBy(content.id)" class="hover:underline" v-if="content.like_count !== 0">
                {{ content.like_count }} Likes
            </a>
            <a href="#" v-else>
                {{ content.like_count }} Likes
            </a>
        </div>
        <a @click.prevent="showPost(content.id)" class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
            <MessageSquareText class="shrink-0 size-5 text-gray-800" />
            {{ content.comment_count }} Comments
        </a>

        <a @click.prevent="openShareModal(content.id)" class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
            <Repeat2 class="shrink-0 size-5 text-gray-800" />
            Share
        </a>

        <a href="#" @click.prevent="openDeleteConfirm(content.id)" v-if="$page.props.auth.user.id === content.user_id" class="inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-red-900 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
            <XCircle class="shrink-0 size-5 text-gray-800" />Delete post
        </a>
    </div>

    <hr class="mt-3 mb-1 border-1 -mx-4" v-if="singlePost">
    <!-- Scrollable Content -->
    <div class="relative overflow-hidden px-4 rounded-2xl bg-white dark:bg-neutral-800 text-left align-middle"
    >
        <div class="flex flex-col h-full relative" v-if="singlePost">
            <!-- Comments Section -->
            <div class="mt-1">
                <p class="text-md font-semibold mb-3 text-gray-800 dark:text-white">
                    Comments
                </p>
                <Comment class="overflow-y-auto max-h-[575px] pb-8 min-h-[125px] -mr-4"
                         :post-id="content.id"
                         :comments="content?.comments || []"
                         :current-user="usePage().props.auth.user"
                         @like-comment="sendCommentLike"
                         @unlike-comment="unlikeComment"
                         @delete-comment="openDeleteCommentConfirm"
                         @comment-added="refreshComments"
                         :singlePost="singlePost"
                />
            </div>
        </div>
    </div>
    <!-- end of if repost empty -->



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

    <!-- Tagged User Modal -->
    <TransitionRoot appear :show="showTaggedModal" as="template" style="position: absolute; z-index: 99999">
        <Dialog as="div" @close="showTaggedModal = false" class="relative">
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
                                People in this post
                            </DialogTitle>
                            <div class="mt-4 max-h-[400px] overflow-y-auto">
                                <div v-if="taggedUsers.length === 0" class="text-center text-gray-500 dark:text-neutral-400">
                                    No Tags
                                </div>
                                <div v-else class="grid grid-cols-2 gap-3">
                                    <Link
                                        v-for="user in taggedUsers"
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
                                    @click="showTaggedModal = false"
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

            <div class="fixed inset-0 overflow-y-auto ">
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
                            class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-neutral-800 py-2 ps-4 pe-1 text-left align-middle shadow-xl transition-all relative max-h-[90vh] flex flex-col"
                        >
                            <!-- Modal Header -->
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900 dark:text-white pb-2 sticky top-0 bg-white dark:bg-neutral-800"
                            >
                                <div class="absolute z-10 -top-1 -right-0">
                                    <button type="button" @click="showPostModal = false"  class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close" data-hs-overlay="#hs-basic-modal">
                                        <span class="sr-only">Close</span>
                                        <svg class="shrink-0 size-4 text-red-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <!-- Post Content -->

                            </DialogTitle>

                            <!-- Scrollable Content -->
                            <div class="flex-grow overflow-y-auto custom-scrollbar pe-2 [&::-webkit-scrollbar]:w-1
                      [&::-webkit-scrollbar-track]:rounded-full
                      [&::-webkit-scrollbar-track]:bg-gray-100
                      [&::-webkit-scrollbar-thumb]:rounded-full
                      [&::-webkit-scrollbar-thumb]:bg-gray-300
                      dark:[&::-webkit-scrollbar-track]:bg-neutral-700
                      dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                                <div>
                                    <div v-if="postDetails" class="space-y-2">

                                        <!-- Post Header -->
                                        <div class="flex items-center">
                                            <Link :href="route('profile.show', postDetails.author.id)" class="shrink-0">
                                                <img class="size-10 rounded-full" :src="postDetails.author.avatar" alt="Avatar">
                                            </Link>
                                            <div class="ms-4">
                                                <div class="flex items-center">
                                                    <Link :href="route('profile.show', postDetails.author.id)" class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700 me-1">{{ content.author.name }}</Link>
                                                    <div class="flex flex-wrap gap-x-1" v-if="postDetails.tags && postDetails.tags.length > 0">
                                                        <p class="text-sm text-gray-800 dark:text-gray-200">with </p>
                                                        <p class="text-sm text-blue-700 dark:text-gray-200">
                                                            {{ formatTags(postDetails.tags.map(tag => tag.name)) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-neutral-500">{{ postDetails.created_at }}</div>
                                            </div>


                                        </div>
                                        <div @click="handleLinkClick" class="text-gray-800 text-wrap text-justify text-sm dark:text-neutral-400 pt-1" v-html="styledTag(postDetails.post)"></div>


                                    </div>
                                    <!-- Post Media -->
                                    <PostMedia
                                        v-if="postDetails.media && postDetails.media.length > 0"
                                        :medias="postDetails.media"
                                        :inside_modal="true"
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
                                        <div @click="handleLinkClick" class="mt-2 text-gray-800 text-wrap text-justify text-sm dark:text-neutral-400" v-html="styledTag(postDetails.repost.post)"></div>

                                        <!-- Image Grid -->
                                        <PostMedia :medias="postDetails.repost.media" :inside_modal="true" v-if="postDetails.repost.media.length > 0" />
                                        <!-- End Image Grid -->

                                    </div>

                                    <hr class="border-1 mt-2">
                                    <div class="flex items-center justify-between py-2">
                                        <div class="inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                                            <a href="#" v-if="content.is_liked" @click.prevent="unlike(content.id)">
                                                <Heart class="shrink-0 size-5 fill-red-500 text-transparent" v-if="content.is_liked" />
                                            </a>
                                            <a href="#" class="inline-flex gap-x-2" @click.prevent="sendLike(content.id)" v-else>
                                                <Heart class="shrink-0 size-5 text-gray-800" />
                                            </a>

                                            <a href="#" @click.prevent="showLikedBy(content.id)" class="hover:underline" v-if="content.like_count !== 0">
                                                {{ content.like_count }} Likes
                                            </a>
                                            <a href="#" v-else>
                                                {{ content.like_count }} Likes
                                            </a>
                                        </div>

                                        <a @click.prevent="showPost(content.id)" class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                                            <MessageSquareText class="shrink-0 size-5 text-gray-800" />
                                            {{ content.comment_count }} Comments

                                        </a>


                                        <a @click.prevent="openShareModal(content.id)" class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                                            <Repeat2 class="shrink-0 size-5 text-gray-800" />
                                            Share
                                        </a>

                                        <a href="#" @click.prevent="openDeleteConfirm(content.id)" v-if="$page.props.auth.user.id === content.user_id" class="inline-flex items-center gap-x-2 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-red-900 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                                            <XCircle class="shrink-0 size-5 text-gray-800" />Delete post
                                        </a>
                                    </div>
                                    <hr class="border-1">
                                </div>
                                <!-- Comments Section -->
                                <div class="mt-3">
                                    <p class="text-md font-semibold mb-3 text-gray-800 dark:text-white">
                                        Comments
                                    </p>
                                    <Comment
                                        :post-id="content.id"
                                        :comments="postDetails?.comments || []"
                                        :current-user="usePage().props.auth.user"
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
