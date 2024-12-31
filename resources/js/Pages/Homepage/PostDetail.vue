<script setup>
import {Head, Link, router, useForm} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {Heart, ImagePlus, Link as LinkIcon, MessageSquareText, X, EllipsisVertical} from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
    post: Object
});

const quillEditor = ref(null);
const fileInput = ref(null);
const previews = ref([]);
const showLinkModal = ref(false);
const linkUrl = ref('');
const linkText = ref('');
const selectedRange = ref(null);
const showDeleteConfirmModal = ref(false);
const commentToDelete = ref(null);

const form = useForm({
    message: '',
    post_id: props.post.id,
    files: []
});

const submitComment = () => {
    form.post(route('user-post.store-comment'), {
        onSuccess: () => {
            form.reset();
            previews.value = [];
        }
    });
};

const sendLike = () => {
    form.post(route('user-post.send-like'), { preserveScroll: true });
};

const unlike = (id) => {
    form.post(route('user-post.unlike'), { preserveScroll: true });
};

const sendCommentLike = (id) => {
    router.post(route('user-post.send-comment-like'), {
        comment_id: id
    }, {
        preserveScroll: true
    });
};

const openDeleteConfirm = (id) => {
    commentToDelete.value = id;
    showDeleteConfirmModal.value = true;
};

const deleteComment = () => {
    if (commentToDelete.value) {
        router.post(route('user-post.delete-comment'), { comment_id: commentToDelete.value }, { preserveScroll: true });
        showDeleteConfirmModal.value = false;
        commentToDelete.value = null;
    }
};

const unlikeComment = (id) => {
    router.post(route('user-post.unlike-comment'), { comment_id: id }, { preserveScroll: true });
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFiles = (event) => {
    const files = event.target?.files;
    if (files) {
        Array.from(files).forEach((file) => {
            const fileReader = new FileReader();
            fileReader.onload = (e) => {
                previews.value.push({
                    url: e.target.result,
                    type: file.type,
                    name: file.name
                });
            };
            form.files.push(file);
            fileReader.readAsDataURL(file);
        });
    }
    if (event.target) {
        event.target.value = '';
    }
};

const removeMedia = (index) => {
    previews.value.splice(index, 1);
    form.files.splice(index, 1);
};

const openLinkDialog = () => {
    selectedRange.value = quillEditor.value.getQuill().getSelection();
    if (selectedRange.value && selectedRange.value.length > 0) {
        linkText.value = quillEditor.value.getQuill().getText(selectedRange.value.index, selectedRange.value.length);
    }
    showLinkModal.value = true;
};

const insertLink = () => {
    if (linkUrl.value) {
        const displayText = linkText.value || linkUrl.value;
        const quill = quillEditor.value.getQuill();

        if (selectedRange.value) {
            if (selectedRange.value.length > 0) {
                quill.deleteText(selectedRange.value.index, selectedRange.value.length);
            }
            quill.insertText(selectedRange.value.index, displayText, { 'link': linkUrl.value });
        } else {
            quill.insertText(quill.getLength() - 1, displayText, { 'link': linkUrl.value });
        }
    }

    // Reset the form
    linkUrl.value = '';
    linkText.value = '';
    showLinkModal.value = false;
    selectedRange.value = null;
};
</script>

<template>
    <Head title="Post Detail" />
    <HomeLayout>
        <div class="flex flex-col bg-white border shadow-sm rounded-xl pt-3 px-4 mb-3 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <Link :href="route('profile.show', post.author.id)" class="flex items-center">
                <div class="shrink-0">
                    <img class="size-10 rounded-full" :src="post.author.avatar" alt="Avatar">
                </div>
                <div class="ms-4">
                    <div class="text-base font-semibold text-gray-800 dark:text-neutral-400 hover:text-blue-700">{{ post.author.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-neutral-500">{{ post.created_at }}</div>
                </div>
            </Link>
            <div class="mt-2 text-gray-800 dark:text-neutral-400" v-html="post.post"></div>

            <!-- Image Grid -->
            <PostMedia :medias="post.media" v-if="post.media.length > 0" />
            <!-- End Image Grid -->

            <div class="inline-flex gap-x-3">
                <Link :href="route('user-post.show-post', post.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <MessageSquareText class="shrink-0 size-4 text-blue-600" />
                    {{ post.comment_count }} Comments

                </Link>
                <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <a href="" @click.prevent="unlike" v-if="post.is_liked" >
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" />
                    </a>
                    <a href="" @click.prevent="sendLike" v-else>
                        <Heart class="shrink-0 size-4 text-gray-500" />
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
                        <h3 class="flex gap-x-1.5 text-sm justify-between font-medium text-gray-800 dark:text-white">
                            {{ comment.user.name }}
                            <p class="mt-1 text-xs italic text-gray-400 dark:text-neutral-400">
                                {{ comment.created_at }}
                            </p>
                        </h3>
                        <p class="mt-1 text-sm text-gray-800 dark:text-neutral-400" v-html="comment.message"></p>
                        <PostMedia :medias="comment.media" :small="true" v-if="comment.media.length > 0" />

                        <div class="inline-flex mt-2 gap-x-3 align-middle">
                            <div class="inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600">
                                <a href="" @click.prevent="unlikeComment(comment.id)" v-if="comment.is_liked">
                                    <Heart class="shrink-0 size-4 fill-red-500 text-transparent" />
                                </a>
                                <a href="" @click.prevent="sendCommentLike(comment.id)" v-else>
                                    <Heart class="shrink-0 size-4 text-gray-500" />
                                </a>
                                {{ comment.like_count }} Likes
                            </div>
                            <a href="#" @click.prevent="openDeleteConfirm(comment.id)" class="inline-flex items-center text-xs text-red-500 hover:text-red-700">
                                <X class="shrink-0 size-4" /> delete comment
                            </a>
                        </div>
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
                            <label for="textarea-label" class="block text-sm font-medium mb-2 dark:text-white">{{ $page.props.auth.user.name }}</label>
                            <QuillEditor
                                ref="quillEditor"
                                v-model:content="form.message"
                                contentType="html"
                                theme="snow"
                                :options="{
                                    modules: {
                                        toolbar: false
                                    },
                                    placeholder: 'Write your comment...'
                                }"
                                class="bg-white dark:bg-neutral-900 rounded-lg first-letter-cap"
                            />

                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" @click="triggerFileInput" class="inline-flex text-xs items-center gap-x-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                                    <ImagePlus class="size-3 shrink-0" />
                                    Add Media
                                </button>
                                <button type="button" @click="openLinkDialog" class="inline-flex text-xs items-center gap-x-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                                    <LinkIcon class="size-3 shrink-0" />
                                    Add Link
                                </button>
                            </div>

                            <!-- Media Preview -->
                            <div class="mt-3" v-if="previews.length > 0">
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                    <div
                                        v-for="(file, index) in previews"
                                        :key="index"
                                        class="relative group aspect-square rounded-lg overflow-hidden"
                                    >
                                        <img
                                            v-if="file.type.startsWith('image/')"
                                            :src="file.url"
                                            :alt="file.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <video
                                            v-else-if="file.type.startsWith('video/')"
                                            class="w-full h-full object-cover"
                                            :title="file.name"
                                        >
                                            <source :src="file.url" :type="file.type" />
                                            Your browser does not support the video tag.
                                        </video>

                                        <!-- Remove button -->
                                        <button
                                            @click="removeMedia(index)"
                                            class="absolute top-1 right-1 p-1 bg-black/50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <X class="size-4 text-white" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden file input -->
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*,video/*"
                                class="hidden"
                                multiple
                                @change="handleFiles"
                            >

                            <button type="submit" class="py-1 mt-3 px-3 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
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

        <!-- Link Modal -->
        <TransitionRoot appear :show="showLinkModal" as="template">
            <Dialog as="div" @close="showLinkModal = false" class="relative z-10">
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
                                    Insert Link
                                </DialogTitle>
                                <div class="mt-4">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">URL</label>
                                        <input
                                            type="url"
                                            v-model="linkUrl"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="https://example.com"
                                        />
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Link Text (optional)</label>
                                        <input
                                            type="text"
                                            v-model="linkText"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Display text"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end space-x-2">
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                        @click="showLinkModal = false"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                        @click="insertLink"
                                    >
                                        Insert
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

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
                                    Delete Comment
                                </DialogTitle>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete this comment? This action cannot be undone.
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
                                        @click="deleteComment"
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
    </HomeLayout>
</template>

<style scoped>
.ql-toolbar {
    display: none !important;
}

.ql-container {
    border: none !important;
}

.ql-editor {
    padding: 0 !important;
}
.text-gray-800 :deep(a) {
    color: #3b82f6; /* Tailwind blue-500 */
    text-decoration: none;
}

.text-gray-800 :deep(a:hover) {
    color: #2563eb; /* Tailwind blue-600 */
    text-decoration: underline;
}
</style>
