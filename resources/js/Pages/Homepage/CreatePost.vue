<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {
    Loader2,
    X,
    ImagePlus,
    ChevronDown
} from "lucide-vue-next";
import {ref, computed} from "vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import PostContent from "@/Components/PostContent.vue";

const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
const MAX_CONTENT_LENGTH = 1000;

const fileInput = ref(null);
const previews = ref([]);
const dragOver = ref(false);
const isLoading = ref(false);
const errors = ref({});

const props = defineProps({
    posts: Object
});

const form = useForm({
    content: '',
    files: [],
    type: 'st',
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

    form.post(route('user-post.store'), {
        onSuccess: () => {
            console.log('Upload successful');
            form.reset();
            previews.value = [];
            isLoading.value = false;
        },
        onError: (errors) => {
            console.error('Upload failed:', errors);
            errors.value = errors;
            isLoading.value = false;
        },
        preserveScroll: true,
    });
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
                    <!-- Textarea with drag-drop zone -->
                    <div
                        class="mb-3 relative"
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                    >
                        <textarea
                            id="post-content"
                            v-model="form.content"
                            :maxlength="MAX_CONTENT_LENGTH"
                            class="p-4 pb-12 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                            :class="{ 'border-2 border-blue-400 border-dashed': dragOver }"
                            placeholder="What's on your mind?"
                        ></textarea>

                        <!-- Character counter -->
                        <div class="absolute bottom-3 right-3 text-sm text-gray-500">
                            {{ remainingChars }} characters remaining
                        </div>
                    </div>

                    <!-- Error messages -->
                    <div v-if="Object.keys(errors).length > 0" class="mb-3">
                        <p v-for="error in errors" :key="error" class="text-red-500 text-sm">
                            {{ error }}
                        </p>
                    </div>

                    <div class="mb-3 grid gap-3 md:flex md:justify-between md:items-center">
                        <div class="inline-flex gap-x-3">
                            <!-- Post type select -->
                            <div class="relative">
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
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                class="hidden"
                                accept="image/*,video/*"
                                @change="handleFiles"
                            />
                            <button
                                type="button"
                                @click="triggerFileInput"
                                :disabled="previews.length >= MAX_FILES"
                                :class="[
                                    'hover:text-blue-600 inline-flex justify-center items-center size-[46px] rounded bg-gray-50 text-gray-800 dark:bg-neutral-700 dark:text-neutral-400',
                                    { 'opacity-50 cursor-not-allowed': previews.length >= MAX_FILES }
                                ]"
                                :title="previews.length >= MAX_FILES ? 'Maximum files reached' : 'Add media'"
                            >
                                <ImagePlus class="shrink-0 size-5" />
                            </button>
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
</style>
