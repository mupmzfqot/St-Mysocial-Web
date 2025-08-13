<script setup>
import {computed, ref, Teleport} from "vue";
import {Download, ZoomIn, ZoomOut} from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    medias: {
        type: Array,
        required: true,
    },
    small: Boolean,
    inside_modal: {
        type: Boolean,
        default: false,
    }
});

let filteredMedia = props.medias.filter((media) => media.mime_type !== "application/pdf");
let docFiles = props.medias.filter((media) => media.mime_type === "application/pdf");

const isModalOpen = ref(false);
const carouselMedia = ref([]);
const currentIndex = ref(0);

const zoomState = ref({
    scale: 1,
    translateX: 0,
    translateY: 0,
    isDragging: false,
    startX: 0,
    startY: 0
});

const handleZoomIn = () => {
    zoomState.value.scale = Math.min(zoomState.value.scale + 0.3, 3);
}

const handleZoomOut = () => {
    zoomState.value.scale = Math.max(zoomState.value.scale - 0.3, 1);
}

const startPan = (e) => {
    if (zoomState.value.scale <= 1) return;
    
    zoomState.value.isDragging = true;
    zoomState.value.startX = e.clientX - zoomState.value.translateX;
    zoomState.value.startY = e.clientY - zoomState.value.translateY;
    
    e.preventDefault();
}

const pan = (e) => {
    if (!zoomState.value.isDragging) return;
    
    zoomState.value.translateX = e.clientX - zoomState.value.startX;
    zoomState.value.translateY = e.clientY - zoomState.value.startY;
    
    e.preventDefault();
}

const endPan = () => {
    zoomState.value.isDragging = false;
}

const resetZoom = () => {
    zoomState.value = {
        scale: 1,
        translateX: 0,
        translateY: 0,
        isDragging: false,
        startX: 0,
        startY: 0
    };
}


const previewMedia = (media, initialIndex = 0) => {
    if (Array.isArray(media)) {
        carouselMedia.value = media;
    } else {
        carouselMedia.value = [media];
    }
    currentIndex.value = initialIndex;
    isModalOpen.value = true;
}

const isVideo = (media) => {
    return media.mime_type.startsWith('video/');
}

const closeModal = () => {
    isModalOpen.value = false;
    currentIndex.value = 0;
}

const nextImage = () => {
    if (currentIndex.value < carouselMedia.value.length - 1) {
        currentIndex.value++;
    }
}

const prevImage = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
}

const handleKeydown = (e) => {
    if (!isModalOpen.value) return;

    if (e.key === 'ArrowRight') {
        nextImage();
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    } else if (e.key === 'Escape') {
        closeModal();
    }
}

const usedIndex = ref(0);
const filteredImages = computed(() => {
    if(isVideo(filteredMedia[0])) {
        usedIndex.value = 1;
        filteredMedia.push(filteredMedia.shift())
    }
    usedIndex.value = 0;
    return filteredMedia;

});

const otherMedia = computed(() => {
    return filteredMedia.filter((media, index) => index !== usedIndex.value);
});
</script>

<template>
    <div :class="['rounded-lg overflow-hidden py-2', filteredMedia.length === 1 ? 'gallery full' : 'gallery grid', small ? 'mb-2 gap-x-2 inline-gallery' : '']">
        <template v-if="filteredMedia.length === 1" >
            <div v-if="isVideo(filteredMedia[0])" class="video-container">
                <video
                    controls
                    :src="filteredMedia[0].original_url"
                    class="w-full h-80"
                    @click.stop="previewMedia(filteredMedia[0])"
                ></video>
            </div>

            <div v-else>
                <img v-if="inside_modal === false"
                     :src="filteredMedia[0].original_url"
                     :alt="filteredMedia[0].name"
                     :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32' : 'w-full']"
                     @click.stop="previewMedia(filteredMedia[0])"
                />

                <img v-else-if="inside_modal === true"
                     :src="filteredMedia[0].original_url"
                     :alt="filteredMedia[0].name"
                     :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32' : 'w-full']"
                     @click.stop="previewMedia(filteredMedia[0])"
                />
            </div>


        </template>

        <template v-else-if="filteredMedia.length === 3" class="grid grid-cols-3 gap-y-0.5">
            <div class="col-span-1">
                <div v-for="(media, index) in [filteredImages[0]]" :key="index">
                    <img
                        :src="media.original_url"
                        alt="Media"
                        class="w-full h-80 object-cover"
                        @click.stop="previewMedia(filteredMedia, index)"
                    />
                </div>
            </div>

            <div class="col-span-1 grid grid-rows-2 gap-y-0.5">
                <div
                    v-for="(media, index) in otherMedia"
                    :key="index"
                    class="relative"
                >
                    <div v-if="isVideo(media)" class="h-40">
                        <video
                            controls
                            :src="media.original_url"
                            class="w-full h-40 object-cover pb-0.5"
                            @click.stop="previewMedia(filteredMedia, index+1)"
                        ></video>
                    </div>
                    <img v-else
                         :src="media.original_url"
                         :alt="media.name"
                         :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32' : 'w-full h-40']"
                         @click.stop="previewMedia(filteredMedia, index+1)"
                    />
                </div>
            </div>
        </template>

        <template v-else>
            <div
                v-for="(media, index) in filteredMedia.slice(0, 4)"
                :key="media.id"
                class="relative"
            >
                <div v-if="isVideo(media)" class="">
                    <video
                        controls
                        :src="media.original_url"
                        class="w-full h-40 object-cover"
                        @click.stop="previewMedia(filteredMedia, index)"
                    ></video>
                </div>
                <img v-else
                    :src="media.original_url"
                    :alt="media.name"
                     :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32 w-24' : 'w-full h-48']"
                    @click.stop="previewMedia(filteredMedia, index)"
                />
                <!-- Overlay untuk gambar lebih dari 4 -->
                <div
                    v-if="index === 3 && filteredMedia.length > 4"
                    class="absolute inset-0 more-overlay hover:opacity-70 cursor-pointer bg-center bg-cover"
                    @click.stop="previewMedia(filteredMedia)"
                >
                    +{{ filteredMedia.length - 4 }}
                </div>
            </div>
        </template>


    </div>
    <div v-if="docFiles.length > 0" class="-mt-2 p-2 border bg-white border-gray-200 rounded-md" v-for="(file, index) in docFiles"
         :key="index">
        <div class="flex items-center gap-x-2">
            <img src="../../images/pdf-icon.svg" class="shrink-0 size-6" alt="document" />
            <a :href="file.original_url" @click.stop target="_blank" title="preview" class="text-sm flex-wrap">
                <span>{{ file.file_name }}</span>
            </a>
            <div class="ms-auto">
                <a :href="file.original_url" @click.stop download type="button" title="download" class="button flex gap-x-1 text-sm hover:text-blue-600">
                    <Download class="shrink-0 size-4"/>
                </a>
            </div>

        </div>
    </div>


    <Teleport to="body">
        <div
        v-if="isModalOpen"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center" style="z-index: 9999"
        @click.stop="(e) => {
            if (e.target === e.currentTarget) {
                closeModal();
            }
        }"
        @keydown.prevent="handleKeydown"
        tabindex="0"
    >
        <div class="relative w-full max-w-4xl mx-4" @click.stop.prevent>
            <!-- Close button -->
            <button
                class="absolute -top-10 right-0 text-white hover:text-gray-300 z-50"
                @click.stop.prevent="closeModal"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18"></path>
                    <path d="M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Main carousel container -->
            <div class="relative bg-gray-900 rounded-lg overflow-hidden">
                <!-- Media display -->
                <div class="flex items-center justify-center min-h-[200px]">
                    <template v-if="carouselMedia[currentIndex]">
                        <div v-if="isVideo(carouselMedia[currentIndex])" class="w-full h-full">
                            <video
                                controls
                                :src="carouselMedia[currentIndex].original_url"
                                class="max-w-full max-h-[80vh] mx-auto"
                            ></video>
                        </div>
                        <div v-else
                            class="zoomable-container w-full h-full flex items-center justify-center relative"
                            @mousedown="startPan"
                            @mousemove="pan"
                            @mouseup="endPan"
                            @mouseleave="endPan"
                        >
                            <img
                                :src="carouselMedia[currentIndex].original_url"
                                :alt="carouselMedia[currentIndex].name"
                                class="max-w-full max-h-[80vh] object-contain cursor-move"
                                :style="{ 
                                    transform: `scale(${zoomState.scale}) translate(${zoomState.translateX}px, ${zoomState.translateY}px)`, 
                                    transition: 'transform 0.3s ease',
                                    width: zoomState.scale > 1 ? `${100 * zoomState.scale}%` : '100%',
                                    height: zoomState.scale > 1 ? `${100 * zoomState.scale}%` : '100%',
                                    transformOrigin: 'center center'
                                }"
                            />
                        </div>
                    </template>
                    <!-- Zoom and Fullscreen Controls -->
            <div class="absolute top-4 right-4 flex space-x-2 z-50" v-if="!isVideo(carouselMedia[currentIndex])">
                <button 
                    class="text-white hover:text-gray-300 bg-black bg-opacity-50 p-2 rounded-full"
                    @click.stop.prevent="handleZoomOut"
                    title="Zoom Out"
                >
                    <ZoomOut class="size-6"/>
                </button>
                <button 
                    class="text-white hover:text-gray-300 bg-black bg-opacity-50 p-2 rounded-full"
                    @click.stop.prevent="resetZoom"
                    title="Reset Zoom"
                >
                    1:1
                </button>
                <button 
                    class="text-white hover:text-gray-300 bg-black bg-opacity-50 p-2 rounded-full"
                    @click.stop.prevent="handleZoomIn"
                    title="Zoom In"
                >
                    <ZoomIn class="size-6"/>
                </button>
            </div>
                </div>

                <!-- Navigation arrows -->
                <button
                    v-if="currentIndex > 0"
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all"
                    @click.stop.prevent="prevImage"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
                <button
                    v-if="currentIndex < carouselMedia.length - 1"
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all"
                    @click.stop.prevent="nextImage"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>

                <!-- Image counter -->
                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                    {{ currentIndex + 1 }} / {{ carouselMedia.length }}
                </div>
            </div>
        </div>
    </div>
    </Teleport>


</template>

<style scoped>
.gallery {
    display: grid;
    gap: 4px;
}

.inline-gallery {
    display: flex !important;
    flex-wrap: wrap;
    gap: 4px;
}

.gallery.full {
    grid-template-columns: 1fr;
}
.gallery.grid {
    grid-template-columns: repeat(2, 1fr);
}
.gallery.grid-auto {
    grid-template-columns: repeat(4, 1fr);
}
.gallery .more-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
}

.video-container {
    position: relative;
    overflow: hidden;
}

.video-container video {
    width: 100%;
    height: 100%;
}

.hs-overlay {
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    pointer-events: none;
}

.hs-overlay[style*="display: none"] {
    opacity: 0;
    pointer-events: none;
}

.zoomable-container {
    overflow: hidden;
    max-width: 100%;
    max-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.zoomable-container.zoomed {
  overflow: auto;
}
.zoomable-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.zoomable-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
}

.zoomable-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 5px;
}

.zoomable-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

</style>
