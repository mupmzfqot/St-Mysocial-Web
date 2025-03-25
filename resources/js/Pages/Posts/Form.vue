<script setup>
import {Head, useForm, Link, router} from "@inertiajs/vue3";
import {
    AlertCircle, PencilLine,
    ChevronDown,
    Loader2,
    X,
    Paperclip,
    SmilePlus,
    SendHorizontal,
    LinkIcon, ChevronRight, List, ListOrdered,
} from "lucide-vue-next";
import {computed, onMounted, reactive, ref, watch} from "vue";
import MultiSelect from "@/Components/MultiSelect.vue";
import QuillEditor from '@/Components/QuillEditor.vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';
import {toast} from "vue3-toastify";
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";

const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime', 'application/pdf'];
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
const showCropModalVisible = ref(false);
const cropImageUrl = ref('');
const cropImageDefault = ref('');
const croppedFile = ref(null);
const cropImageType = ref('');
const cropImageIndex = ref(null);
const cropper = ref(null);

const props = defineProps({
    post: Object,
    stUsers: Object,
    defaultType: String,
    title: String
});

const form = useForm({
    id: props.post?.id || null,
    content: props.post?.post || '',
    files: props.post?.media || [],
    type: props.post?.type || props.defaultType,
    userTags: props.post?.tags.map(tag => tag.user_id) || []
});

// Initialize media previews for existing posts
const initializeMediaPreviews = () => {
    if (props.post && props.post.media && props.post.media.length > 0) {
        previews.value = props.post.media.map(media => ({
            url: media.preview_url || media.original_url,
            type: media.mime_type,
            name: media.file_name,
            id: media.id
        }));
    }
};

const isValid = computed(() => {
    return form.content.trim().length > 0 || form.files.length > 0;
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const showCropModal = (file, index) => {
    cropImageUrl.value = file.url
    cropImageType.value = file.type
    cropImageDefault.value = file
    showCropModalVisible.value = true
    cropImageIndex.value = index
}

const handleCropImage = (croppedImage) => {
  croppedFile.value = croppedImage
  showCropModalVisible.value = false
}

const handleCrop = (defaultImage, index) => {
    const { coordinates, canvas, } = cropper.value.getResult();
    const filename = defaultImage.name.split('.')[0];

    coordinates.value = coordinates;
    // use canvas.toDataURL with format PNG
    const croppedImageDataURL = canvas.toDataURL('image/png'); 
    const byteString = atob(croppedImageDataURL.split(',')[1]);
    const arrayBuffer = new ArrayBuffer(byteString.length);
    const uint8Array = new Uint8Array(arrayBuffer);

    // convert byte string to array buffer
    for (let i = 0; i < byteString.length; i++) {
        uint8Array[i] = byteString.charCodeAt(i);
    }

    // remove previous image from list
    removeMedia(index);

    // create file MIME_TYPE image/png
    const file = new File([uint8Array], `${filename}.png`, { type: 'image/png' });
    handleFiles({ target: { files: [file] } });
    showCropModalVisible.value = false;
}

watch(() => props.post?.tags, (newTags) => {
  form.userTags = newTags?.map(tag => tag.id) || [];
});

onMounted(() => {
    initializeMediaPreviews();
    cropper.value = ref('cropper');
    if (quillEditor.value) {
        quillEditor.value.setContent(props.post?.post || '');
    }
})

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

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    dragOver.value = false;
};

const cleanDataBeforeSubmit = () => {
    let editorContent = quillEditor.value.getContent();
    editorContent = editorContent.replace(/(<p><br><\/p>)+$/, '');
    editorContent = editorContent.replace(/<script[^>]*>([\S\s]*?)<\/script>/g, '');
    editorContent = editorContent.replace(/<iframe[^>]*>[\S\s]*?<\/iframe>/g, ''); 

    if (!editorContent.trim()) {
        console.error("Editor content is empty after cleaning.");
    }

    return editorContent;
}

const submit = () => {
    if (!isValid.value) return;

    isLoading.value = true;
    errors.value = {};
    showSuccessMessage.value = false;
    showErrorMessage.value = false;
    form.content = cleanDataBeforeSubmit();

    let url = props.post?.id ? route('post.update', props.post.id) : route('post.store');

    form.post(url, {
        onSuccess: (page) => {
            const flash = page.props.flash;

            if (flash.success) {
                successMessage.value = flash.success;
                showSuccessMessage.value = true;
                window.location.reload();
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

const openLinkDialog = () => {
    showLinkModal.value = true;
    if (selectedRange.value) {
        const quillContent = quillEditor.value.getContent();
        const selectedText = quillContent.ops[selectedRange.value.index].insert;
        linkText.value = selectedText;
    }
};

const insertLink = () => {
    if (linkUrl.value) {
        const displayText = linkText.value || linkUrl.value;

        let finalUrl = linkUrl.value;
        if(linkUrl.value.includes('.') && !linkUrl.value.startsWith('http://') && !linkUrl.value.startsWith('https://')) {
            finalUrl = `https://${linkUrl.value}`;
        }

        if (selectedRange.value) {
            if (selectedRange.value.length > 0) {
                quillEditor.value.deleteText(selectedRange.value.index, selectedRange.value.length);
            }
            quillEditor.value.insertText(selectedRange.value.index, displayText);
            quillEditor.value.formatText(selectedRange.value.index, displayText.length, 'link', finalUrl);
        } else {
            const currentIndex = quillEditor.value.getLength() - 1;
            quillEditor.value.insertText(currentIndex, displayText);
            quillEditor.value.formatText(currentIndex, displayText.length, 'link', finalUrl);
        }
    }

    linkUrl.value = '';
    linkText.value = '';
    showLinkModal.value = false;
    selectedRange.value = null;
};

const showEmojiPicker = ref(false);
const onSelectEmoji = (emoji) => {
    if (quillEditor.value) {
        const range = quillEditor.value.getSelection();
        quillEditor.value.insertText(range ? range.index: 0, emoji.i);
    }
    showEmojiPicker.value = false;
};

const addNumberList = () => {
    if (quillEditor.value) {
        quillEditor.value.format('list', false);
        quillEditor.value.format('list', 'ordered');
    }
};

const addBulletList = () => {
    if (quillEditor.value) {
        quillEditor.value.format('list', false);
        quillEditor.value.format('list', 'bullet');
    }
};

</script>

<template>
    <Head :title="title"/>
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                {{ title }}
            </li>
        </Breadcrumbs>
        <!-- Card Section -->
        <div class="max-w-screen-lg">
            <div class="mx-auto p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
                <div class="mb-2">
                    <label class="font-semibold text-gray-800 dark:text-neutral-200">{{ title }}</label>
                </div>
                <!-- Media Preview -->
                <div class="mb-3" v-if="previews.length > 0">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-gray-600 dark:text-gray-300">Media preview</label>
                        <span class="text-sm text-gray-500">{{ previews.length }}/{{ MAX_FILES }} files</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-7 gap-2 mt-3">
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
                                class="absolute top-1 right-2 p-1 bg-red-500 rounded-full text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                title="Remove media"
                            >
                                <X class="size-4" />
                            </button>
                            <button
                                v-if="file.type.startsWith('image/')"
                                @click="showCropModal(file, index)"
                                class="absolute top-1 right-9 p-1 bg-orange-400 rounded-full text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                title="Crop Image"
                            >
                                <PencilLine class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <MultiSelect :stUsers="stUsers" v-model="form.userTags" v-if="!$page.props.auth.user.roles.includes('public_user')" />

                    <div class="mb-0 relative"
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                    >
                        <QuillEditor ref="quillEditor" @update:value="form.content = $event" class="min-h-[120px] text-sm border border-gray-200"/>
                        <div
                            v-if="dragOver"
                            class="absolute inset-0 bg-blue-500 bg-opacity-10 border-2 border-blue-500 border-dashed rounded-lg flex items-center justify-center"
                        >
                            <p class="text-blue-500 font-medium">Drop files here</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between mt-1">
                        <!-- Left Icons -->
                        <div class="flex space-x-1 text-gray-00">
                            <div class="relative" v-if="defaultType === 'st'">
                                <select
                                    v-model="form.type"
                                    class="appearance-none h-[40px] pl-4 pr-10 min-w-[120px] rounded-lg border border-gray-200 text-sm dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 focus:border-blue-500 focus:ring-blue-500 bg-gray-50 dark:bg-neutral-700"
                                >
                                    <option value="st">Team ST</option>
                                    <option value="public" v-if="defaultType !== 'st'">Public</option>
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
                                        Insert Files(.pdf, .jpg. jpeg, .mp4), max file size: 10MB
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
                                    class="absolute z-50 mt-2"
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

                            <div class="hs-tooltip [--placement:bottom] inline-block">
                                <button @click="addBulletList" type="button"
                                        class="hs-tooltip-toggle size-10 inline-flex justify-center items-center gap-2 rounded-md bg-gray-50 border border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-50 focus:border-blue-200 focus:text-blue-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white dark:focus:bg-white/10 dark:focus:border-white/10 dark:focus:text-white">
                                    <List class="shrink-0 size-4"/>
                                    <span
                                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                        role="tooltip">
                                        Bullet List
                                    </span>
                                </button>
                            </div>
                            <div class="hs-tooltip [--placement:bottom] inline-block">
                                <button @click="addNumberList" type="button"
                                        class="hs-tooltip-toggle size-10 inline-flex justify-center items-center gap-2 rounded-md bg-gray-50 border border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-50 focus:border-blue-200 focus:text-blue-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white dark:focus:bg-white/10 dark:focus:border-white/10 dark:focus:text-white">
                                    <ListOrdered class="shrink-0 size-4"/>
                                    <span
                                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                        role="tooltip">
                                        Number List
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
                            <Link :href="route('home')" type="button"
                                  class="py-2 px-4 inline-flex items-center  text-sm font-medium rounded-lg border border-gray-200 text-gray-800 hover:border-blue-600 hover:text-blue-600 focus:outline-none focus:border-blue-600 focus:text-blue-600 disabled:opacity-50">
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="!isValid || isLoading"
                                class="flex text-sm items-center space-x-2 bg-blue-600 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition"
                            >
                                <Loader2 v-if="isLoading" class="animate-spin size-4"/>
                                <SendHorizontal v-else class="shrink-0 size-4"/>
                                <span>{{ isLoading ? 'Posting...' : title }}</span>
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

    </AuthenticatedLayout>

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
                <div class="fixed inset-0 bg-black/25"/>
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
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

    <TransitionRoot
        :show="showCropModalVisible"
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
        >
        <div class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg shadow-lg p-4 w-1/2">
                <Cropper
                    :src="cropImageUrl"
                    :type="cropImageType"
                    :aspect-ratio="1"
                    :rectangular="true"
                    style="max-height: 80svh;"
                    :size-restrictions-algorithm="pixelsRestriction"
                    ref="cropper"
                    @uploaded="handleCropImage"
                />
                <div class="flex justify-end gap-x-2 pt-4">
                    <button @click="showCropModalVisible = false" type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm 
                        font-medium rounded-lg border border-gray-400 text-gray-700 hover:text-red-600 
                        focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Cancel
                    </button>
                    <button @click="handleCrop(cropImageDefault, cropImageIndex)" type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Crop Image
                    </button>
                </div>
            </div>
        </div>
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
