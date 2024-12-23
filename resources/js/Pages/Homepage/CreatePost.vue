<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {AlertCircle, ChevronDown, ImagePlus, Loader2, X} from "lucide-vue-next";
import {computed, ref} from "vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import PostContent from "@/Components/PostContent.vue";
import MultiSelect from "@/Components/MultiSelect.vue";
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
const MAX_CONTENT_LENGTH = 1000;

const fileInput = ref(null);
const previews = ref([]);
const dragOver = ref(false);
const isLoading = ref(false);
const errors = ref({});
const showSuccessMessage = ref(false);
const successMessage = ref('');
const showErrorMessage = ref(false);
const errorMessage = ref('');
const quillEditor = ref(null);

const props = defineProps({
    posts: Object,
    stUsers: Object,
    defaultType: String
});

const form = useForm({
    content: '',
    files: [],
    type: props.defaultType,
    userTags: []
});

const remainingChars = computed(() => {
    return MAX_CONTENT_LENGTH - form.content.length;
});

const isValid = computed(() => {
    return form.content.trim().length > 0 || form.files.length > 0;
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const validateFile = (file) => {
    if (!ALLOWED_TYPES.includes(file.type)) {
        errors.value.file = 'File type not supported';
        return false;
    }
    if (file.size > MAX_FILE_SIZE) {
        errors.value.file = 'File size should be less than 10MB';
        return false;
    }
    return true;
};

const handleFiles = (event) => {
    const files = event.target?.files || event.dataTransfer?.files;
    errors.value = {};

    if (files.length + previews.value.length > MAX_FILES) {
        errors.value.file = `Maximum ${MAX_FILES} files allowed`;
        return;
    }

    Array.from(files).forEach((file) => {
        if (validateFile(file)) {
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
        }
    });

    if (event.target) {
        event.target.value = ''; // Reset input
    }
};

const removeMedia = (index) => {
    previews.value.splice(index, 1);
    form.files.splice(index, 1);
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    handleFiles(e);
};

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    dragOver.value = false;
};

const submit = () => {
    if (!isValid.value) return;

    isLoading.value = true;
    errors.value = {};
    showSuccessMessage.value = false;
    showErrorMessage.value = false;

    form.post(route('user-post.store'), {
        onSuccess: (page) => {
            const flash = page.props.flash;

            if (flash.success) {
                successMessage.value = flash.success;
                showSuccessMessage.value = true;
            }

            form.reset();
            previews.value = [];
            isLoading.value = false;
        },
        onError: (error) => {
            errorMessage.value = Object.values(error)[0];
            showErrorMessage.value = true;
            isLoading.value = false;
        },
        preserveScroll: true,
    });
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
    <Head title="Create New Post" />
    <HomeLayout>
        <!-- Card Section -->
        <div class="w-full">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow p-4 dark:bg-neutral-800">
                <div class="mb-2">
                    <label class="font-semibold text-gray-800 dark:text-neutral-200">New Post</label>
                </div>

                <!-- Media Preview -->
                <div class="mb-3" v-if="previews.length > 0">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-gray-600 dark:text-gray-300">Media preview</label>
                        <span class="text-sm text-gray-500">{{ previews.length }}/{{ MAX_FILES }} files</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mt-3">
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
                                class="absolute top-2 right-2 p-1 bg-black bg-opacity-50 rounded-full text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                title="Remove media"
                            >
                                <X class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" mt-2>
                    <!-- Quill Editor with drag-drop zone -->
                    <div
                        class="mb-0 relative"
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                    >
                        <QuillEditor
                            ref="quillEditor"
                            v-model:content="form.content"
                            contentType="html"
                            :options="{
                                placeholder: 'What\'s on your mind?',
                                modules: {
                                    toolbar: false
                                }
                            }"
                            :style="{
                                height: '150px',
                                marginBottom: '5px'
                            }"
                            class="bg-white dark:bg-neutral-900 rounded-lg first-letter-cap"
                        />
                        <div
                            v-if="dragOver"
                            class="absolute inset-0 bg-blue-500 bg-opacity-10 border-2 border-blue-500 border-dashed rounded-lg flex items-center justify-center"
                        >
                            <p class="text-blue-500 font-medium">Drop files here</p>
                        </div>
                    </div>

                    <MultiSelect :stUsers="stUsers" v-model="form.userTags" v-if="!$page.props.auth.user.roles.includes('public_user')" />

                    <!-- Error messages -->
                    <div v-if="Object.keys(errors).length > 0" class="mb-3 px-2 py-1">
                        <p v-for="error in errors" :key="error" class="text-red-500 text-sm">
                            {{ error }}
                        </p>
                    </div>

                    <div class="mb-3 grid gap-3 md:flex md:justify-between md:items-center">
                        <div class="inline-flex gap-x-3">
                            <!-- Post type select -->
                            <div class="relative" v-if="defaultType === 'st'">
                                <select
                                    v-model="form.type"
                                    class="appearance-none h-[46px] pl-4 pr-10 min-w-[120px] rounded-lg border border-gray-200 text-sm dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 focus:border-blue-500 focus:ring-blue-500 bg-gray-50 dark:bg-neutral-700"
                                >
                                    <option value="st">ST User</option>
                                    <option value="public">Public</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500 dark:text-neutral-400">
                                    <ChevronDown class="size-4" />
                                </div>
                            </div>

                            <!-- Media upload button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                                @click="triggerFileInput"
                            >
                                <ImagePlus class="w-4 h-4"/>
                                Add media
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                                @click="insertLink"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                                Add link
                            </button>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                class="hidden"
                                accept="image/*,video/*"
                                @change="handleFiles"
                            />

                        </div>

                        <!-- Submit button -->
                        <button
                            type="submit"
                            :disabled="!isValid || isLoading"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Loader2 v-if="isLoading" class="animate-spin size-4" />
                            <span>{{ isLoading ? 'Posting...' : 'Create Post' }}</span>
                        </button>
                    </div>
                </form>

                <div v-if="showSuccessMessage" class="mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900">
                    <p class="text-green-800 dark:text-green-200">{{ successMessage }}</p>
                </div>

                <div v-if="showErrorMessage" class="w-full mt-4 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900 text-sm inline-flex items-center gap-x-2 text-red-800 dark:text-red-200">
                    <AlertCircle class="shrink-0 size-4" /><span>{{ errorMessage }}</span>
                </div>
            </div>
        </div>

        <!-- My Posts -->
        <div>
            <p class="relative py-4 mx-2 text-lg font-bold text-gray-800 dark:text-white">
                My Recent Posts
            </p>
        </div>

        <PostContent :posts="posts" :post-status="true" />
    </HomeLayout>
</template>

<style scoped>
.aspect-square {
    aspect-ratio: 1 / 1;
}

.first-letter-cap :deep(.ql-editor p:first-of-type::first-letter) {
    text-transform: uppercase !important;
}

.ql-editor {
  text-transform: capitalize !important;
}
</style>
