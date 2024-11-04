<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { CircleStencil, Cropper, Preview } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import 'vue-advanced-cropper/dist/theme.classic.css'
import {useForm} from "@inertiajs/vue3";

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
    }
})

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
                type: 'image/jpeg', // Set the file type
                lastModified: Date.now() // Optional: set the last modified date
            });

            useForm({
                file: file,
                type: props.type,
            }).post(props.url, {
                onSuccess: (res) => {
                    if (res.collection_name === 'avatar') {
                        props.defaultImage = res;
                    }
                    reset();

                },
                onError: (errors) => {
                    console.error('Upload failed:', errors);
                },
                // You may want to use `preserveScroll` to prevent scroll reset on the page after upload
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

    <div class="flex flex-wrap items-center">
        <div class="cropper-wrapper">
            <Cropper
                ref="cropperRef"
                class="upload-example-cropper"
                :src="image.src"
                :stencil-component="CircleStencil"
                :stencil-size="{ width: 300, height: 300 }"
                :stencil-props="{ handlers: {}, movable: false, resizable: false, aspectRatio: 1 }"
                image-restriction="stencil"
                @change="onChange"
            />
        </div>

        <div class="group" v-if="!image.src">
            <span v-if="!defaultImage" class="group-has-[div]:hidden flex shrink-0 justify-center items-center size-20 border-2 border-dotted border-gray-300 text-gray-400 cursor-pointer rounded-full hover:bg-gray-50 dark:border-neutral-700 dark:text-neutral-600 dark:hover:bg-neutral-700/50">
                <svg class="shrink-0 size-7" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <circle cx="12" cy="10" r="3"></circle>
                  <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
                </svg>
            </span>

            <img v-if="defaultImage" class="inline-block size-[100px] rounded-full" :src="defaultImage.original_url" alt="Avatar">

        </div>
        <div class="grow px-4">
            <div class="flex items-center gap-x-2">
                <button v-if="!image.src"
                    type="button"
                    @click="fileInputRef.click()"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <input type="file" hidden ref="fileInputRef" @change="loadImage" accept="image/*" />
                    Upload Photo
                </button>
                <form @submit.prevent="uploadPhoto" v-if="image.src">
                    <button
                            type="submit"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        Save changes
                    </button>
                </form>
                <button
                    type="button"
                    @click="reset"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cropper-wrapper {
    max-width: 300px;
    max-height: 300px;
    margin-bottom: 16px;
}

.upload-example-cropper {
    overflow: hidden;
}
</style>
