<script setup>
import {Head, Link, useForm} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {Heart, ImagePlus, Link as LinkIcon, MessageSquareText, X} from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import PostContent from "@/Components/PostContent.vue";
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { ref } from 'vue'

const props = defineProps({
    post: Object
});

const quillEditor = ref(null);
const fileInput = ref(null);
const previews = ref([]);

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

const insertLink = () => {
    const url = prompt('Enter URL:');
    if (url) {
        const range = quillEditor.value.getQuill().getSelection();
        if (range) {
            quillEditor.value.getQuill().format('link', url);
        } else {
            quillEditor.value.getQuill().insertText(quillEditor.value.getQuill().getLength() - 1, url, {
                'link': url
            });
        }
    }
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
            <div class="mt-2 text-gray-500 dark:text-neutral-400" v-html="post.post"></div>

            <!-- Image Grid -->
            <PostMedia :medias="post.media" v-if="post.media.length > 0" />
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
                        <h3 class="flex gap-x-1.5 text-sm font-medium text-gray-800 dark:text-white">
                            {{ comment.user.name }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400" v-html="comment.message"></p>
                        <p class="mt-1 text-xs italic text-gray-600 dark:text-neutral-400">
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
                                <button type="button" @click="insertLink" class="inline-flex text-xs items-center gap-x-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
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
</style>
