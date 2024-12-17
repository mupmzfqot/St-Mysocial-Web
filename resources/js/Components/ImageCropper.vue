<script setup>
import { ref, onBeforeUnmount, computed } from 'vue'
import { CircleStencil, RectangleStencil, Cropper, Preview } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import 'vue-advanced-cropper/dist/theme.classic.css'
import { useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";
import 'vue-toast-notification/dist/theme-sugar.css';

// Function to detect the actual image type
function getMimeType(file, fallback = null) {
    const byteArray = new Uint8Array(file).subarray(0, 4)
    let header = ''
    for (let i = 0; i < byteArray.length; i++) {
        header += byteArray[i].toString(16)
    }
    switch (header) {
        case '89504e47':
            return 'image/png'
        case '47494638':
            return 'image/gif'
        case 'ffd8ffe0':
        case 'ffd8ffe1':
        case 'ffd8ffe2':
        case 'ffd8ffe3':
        case 'ffd8ffe8':
            return 'image/jpeg'
        default:
            return fallback
    }
}

const props = defineProps({
    type: {
        type: String,
        required: true,
    },
    url: {
        type: String,
        required: true,
    },
    defaultImage: {
        type: String,
        required: false,
    },
    aspectRatio: {
        type: Number,
        default: 1
    },
    rectangular: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['uploaded']);

// Reactive variables
const image = ref({
    src: null,
    type: null,
})

const result = ref({
    coordinates: null,
    image: null,
})

const cropperRef = ref(null)
const fileInputRef = ref(null)

const toast = useToast();

// Computed properties for cropper settings
const stencilComponent = computed(() => props.rectangular ? RectangleStencil : CircleStencil);
const stencilSize = computed(() => {
    return props.rectangular 
        ? { width: 600, height: 200 }  // Cover photo size
        : { width: 300, height: 300 }; // Avatar size
});

const stencilProps = computed(() => ({
    handlers: {},
    movable: true,
    resizable: props.rectangular, // Only allow resizing for cover photos
    aspectRatio: props.aspectRatio,
}));

// Methods
function crop() {
    const { canvas } = cropperRef.value.getResult()
    canvas.toBlob((blob) => {
        // Do something with blob: upload to a server, download, etc.
    }, image.value.type)
}

function reset() {
    image.value = {
        src: null,
        type: null,
    }
    result.value = {
        coordinates: null,
        image: null,
    }
}

function onChange({ coordinates, canvas }) {
    result.value = {
        coordinates,
        image: canvas.toDataURL(),
    }
}

function loadImage(event) {
    const { files } = event.target
    if (files && files[0]) {
        if (image.value.src) {
            URL.revokeObjectURL(image.value.src)
        }
        const blob = URL.createObjectURL(files[0])

        const reader = new FileReader()
        reader.onload = (e) => {
            image.value = {
                src: blob,
                type: getMimeType(e.target.result, files[0].type),
            }
        }
        reader.readAsArrayBuffer(files[0]);
    }
}

const uploadPhoto = () => {
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
        canvas.toBlob((blob) => {
            if (!blob) return;

            // Convert the Blob to a File
            const file = new File([blob], `${props.type}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });

            useForm({
                file: file,
                type: props.type,
            }).post(props.url, {
                onSuccess: (res) => {
                    if (res.collection_name === props.type) {
                        props.defaultImage = res;
                    }
                    reset();
                    emit('uploaded'); // Emit event when upload is successful
                    
                    // Show success notification
                    toast.success(`${props.type === 'avatar' ? 'Profile picture' : 'Cover photo'} updated successfully!`, {
                        position: 'top-right',
                        duration: 3000,
                        dismissible: true
                    });
                },
                onError: (errors) => {
                    console.error('Upload failed:', errors);
                    // Show error notification
                    toast.error(`Failed to update ${props.type === 'avatar' ? 'profile picture' : 'cover photo'}. Please try again.`, {
                        position: 'top-right',
                        duration: 5000,
                        dismissible: true
                    });
                },
                preserveScroll: true,
            });
        }, 'image/jpeg');
    }
}

// Cleanup
onBeforeUnmount(() => {
    if (image.value.src) {
        URL.revokeObjectURL(image.value.src)
    }
})
</script>

<template>
    <div class="flex flex-col items-center w-full">
        <div class="cropper-wrapper w-full" :class="{ 'cover-photo': props.rectangular }">
            <Cropper
                ref="cropperRef"
                class="upload-example-cropper"
                :src="image.src"
                :stencil-component="stencilComponent"
                :stencil-size="stencilSize"
                :stencil-props="stencilProps"
                image-restriction="stencil"
                @change="onChange"
            />
        </div>

        <div class="group mt-4 w-full" v-if="!image.src">
            <div class="flex flex-col items-center">
                <span v-if="!props.defaultImage" 
                    class="group-has-[div]:hidden flex shrink-0 justify-center items-center border-2 border-dotted border-gray-300 text-gray-400 cursor-pointer hover:bg-gray-50 dark:border-neutral-700 dark:text-neutral-600 dark:hover:bg-neutral-700/50"
                    :class="[
                        props.rectangular 
                            ? 'w-full max-w-lg h-32 rounded-lg' 
                            : 'size-20 rounded-full'
                    ]"
                >
                    <svg class="shrink-0 size-7" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 5h16c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </span>

                <img v-if="props.defaultImage" 
                    :class="[
                        props.rectangular 
                            ? 'w-full max-w-lg h-32 rounded-lg object-cover' 
                            : 'size-20 rounded-full object-cover'
                    ]"
                    :src="props.defaultImage.original_url" 
                    :alt="props.rectangular ? 'Cover Photo' : 'Avatar'"
                >
            </div>

            <div class="flex items-center justify-center gap-x-2 mt-4">
                <button
                    type="button"
                    @click="fileInputRef.click()"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <input type="file" hidden ref="fileInputRef" @change="loadImage" accept="image/*" />
                    Upload Photo
                </button>
            </div>
        </div>

        <form @submit.prevent="uploadPhoto" v-if="image.src" class="mt-4 flex gap-2">
            <button
                type="submit"
                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
            >
                Save changes
            </button>
            <button
                type="button"
                @click="reset"
                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50"
            >
                Cancel
            </button>
        </form>
    </div>
</template>

<style scoped>
.cropper-wrapper {
    position: relative;
    width: 100%;
    max-width: 100%;
    aspect-ratio: 1;
    margin: 0 auto;
}

.cropper-wrapper.cover-photo {
    aspect-ratio: 3/1;
    max-height: 60vh;
}

.upload-example-cropper {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

@media (max-width: 640px) {
    .cropper-wrapper {
        aspect-ratio: 1;
        height: 300px;
    }
    
    .cropper-wrapper.cover-photo {
        aspect-ratio: 3/2;
        height: 200px;
    }
}
</style>
