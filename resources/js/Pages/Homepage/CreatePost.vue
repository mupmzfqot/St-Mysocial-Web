<script setup>
import {Head, useForm, Link, router} from "@inertiajs/vue3";
import {AlertCircle, ChevronDown, Loader2, X, Paperclip, SmilePlus, SendHorizontal, LinkIcon, UserPlus} from "lucide-vue-next";
import {computed, onMounted, ref} from "vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import PostContent from "@/Components/PostContent.vue";
import MultiSelect from "@/Components/MultiSelect.vue";
import {Delta, QuillEditor} from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';

const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime', 'application/pdf'];
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
const showLinkModal = ref(false);
const linkUrl = ref('');
const linkText = ref('');
const selectedRange = ref(null);

const props = defineProps({
    stUsers: Object,
    defaultType: String,
    requestUrl: {
        type: String,
        required: true,
    }
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
            router.visit(route('home'))
        },
        onError: (error) => {
            errorMessage.value = Object.values(error)[0];
            showErrorMessage.value = true;
            isLoading.value = false;
        },
        preserveScroll: true,
    });
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

onMounted(() => {
    if (quillEditor.value) {
        const q = quillEditor.value.getQuill();
        q.on('text-change', (d, _, source) => {
            if (source !== 'api') {
                const sel = q.getSelection();
                if (!sel) return;

                const [line, ] = q.getLine(sel.index);
                if (!line.children) { return }

                const val = line.children.head.value();
                if (val.length && val[0] === val[0].toLowerCase()) {
                    q.updateContents(
                        new Delta().retain(q.getIndex(line.children.head)).delete(1).insert(val[0].toUpperCase())
                        , 'api')
                }
            }
        });
    }
});

const showEmojiPicker = ref(false);
const onSelectEmoji = (emoji) => {
    if (quillEditor.value) {
        const quill = quillEditor.value.getQuill();
        const range = quill.getSelection() || { index: quill.getLength() };
        quill.insertText(range.index, emoji.i);
        quill.setSelection(range.index + emoji.i.length);
    }
    showEmojiPicker.value = false;
};
</script>

<template>
    <Head title="Create New Post" />
    <HomeLayout>
        <!-- Card Section -->
        <div class="w-full">
            <div class="mx-auto p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
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

                            <div
                                v-else-if="file.type === 'application/pdf'"
                                class="w-full h-full flex items-center justify-center bg-gray-100"
                            >
                                <img
                                    src="../../../images/pdf-icon.svg"
                                    alt="PDF Icon"
                                    class="w-1/2 h-1/2 object-contain"
                                />
                            </div>

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
                    <MultiSelect :stUsers="stUsers" v-model="form.userTags" v-if="!$page.props.auth.user.roles.includes('public_user')" />

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

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between">
                        <!-- Left Icons -->
                        <div class="flex space-x-1 text-gray-00">
                            <div class="relative" v-if="defaultType === 'st'">
                                <select
                                    v-model="form.type"
                                    class="appearance-none h-[40px] pl-4 pr-10 min-w-[120px] rounded-lg border border-gray-200 text-sm dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 focus:border-blue-500 focus:ring-blue-500 bg-gray-50 dark:bg-neutral-700"
                                >
                                    <option value="st">Team ST</option>
                                    <option value="public">Public</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500 dark:text-neutral-400">
                                    <ChevronDown class="size-4" />
                                </div>
                            </div>

                            <div class="hs-tooltip [--placement:bottom] inline-block">
                                <button @click="triggerFileInput" type="button"
                                        class="hs-tooltip-toggle size-10 inline-flex justify-center items-center gap-2 rounded-md bg-gray-50 border border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-50 focus:border-blue-200 focus:text-blue-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white dark:focus:bg-white/10 dark:focus:border-white/10 dark:focus:text-white">
                                    <Paperclip class="shrink-0 size-4"/>
                                    <span
                                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                        role="tooltip">
                                        Insert Files(.pdf, .jpg. jpeg, .mp4)
                                    </span>
                                </button>
                            </div>

                            <div class="hs-tooltip [--placement:bottom] inline-block">
                                <button @click="showEmojiPicker = !showEmojiPicker" type="button"
                                        class="hs-tooltip-toggle size-10 inline-flex justify-center items-center gap-2 rounded-md bg-gray-50 border border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-50 focus:border-blue-200 focus:text-blue-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white dark:focus:bg-white/10 dark:focus:border-white/10 dark:focus:text-white">
                                    <SmilePlus class="shrink-0 size-4"/>
                                    <span
                                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                        role="tooltip">
                                        Emoji
                                    </span>
                                </button>
                                <EmojiPicker
                                    v-if="showEmojiPicker"
                                    @select="onSelectEmoji"
                                    class="absolute z-50"
                                />
                            </div>

                            <div class="hs-tooltip [--placement:bottom] inline-block">
                                <button @click="openLinkDialog" type="button"
                                        class="hs-tooltip-toggle size-10 inline-flex justify-center items-center gap-2 rounded-md bg-gray-50 border border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-50 focus:border-blue-200 focus:text-blue-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white dark:focus:bg-white/10 dark:focus:border-white/10 dark:focus:text-white">
                                    <LinkIcon class="shrink-0 size-4"/>
                                    <span
                                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                        role="tooltip">
                                        Insert Link
                                    </span>
                                </button>
                            </div>
                        </div>

                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            class="hidden"
                            accept="image/*,video/*,application/pdf"
                            @change="handleFiles"
                        />

                        <div class="flex items-center justify-between gap-x-2">
                            <Link :href="route('home')" type="button" class="py-2 px-4 inline-flex items-center  text-sm font-medium rounded-lg border border-gray-200 text-gray-800 hover:border-blue-600 hover:text-blue-600 focus:outline-none focus:border-blue-600 focus:text-blue-600 disabled:opacity-50">
                                Cancel</Link>
                            <button
                                type="submit"
                                :disabled="!isValid || isLoading"
                                class="flex text-sm items-center space-x-2 bg-blue-600 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition"
                            >
                                <Loader2 v-if="isLoading" class="animate-spin size-4" />
                                <SendHorizontal v-else class="shrink-0 size-4" />
                                <span>{{ isLoading ? 'Posting...' : 'Create Post' }}</span>
                            </button>
                        </div>
                    </div>


                </form>

                <!-- Error messages -->
                <div v-if="Object.keys(errors).length > 0" class="mb-3 px-2 py-1">
                    <p v-for="error in errors" :key="error" class="text-red-500 text-sm">
                        {{ error }}
                    </p>
                </div>

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

        <PostContent :post-status="true" :requestUrl="requestUrl" />
    </HomeLayout>

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
