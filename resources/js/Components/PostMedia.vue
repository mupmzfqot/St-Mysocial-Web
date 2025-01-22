<script setup xmlns="http://www.w3.org/1999/html">
import {computed, ref, Teleport} from "vue";

const props = defineProps({
    medias: {
        type: Array,
        required: true,
    },
    small: Boolean
});

const isModalOpen = ref(false);
const carouselMedia = ref([]);
const currentIndex = ref(0);
const filteredIndex = ref(0);

const previewMedia = (media) => {
    if (Array.isArray(media)) {
        carouselMedia.value = media;
    } else {
        carouselMedia.value = [media];
    }
    currentIndex.value = 0;
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

const filteredImages = computed(() => {
    return props.medias.filter((media) => media.mime_type.startsWith('image/'))
});

const otherMedia = computed(() => {
    let i = 0;
    if (props.medias[0].mime_type.startsWith('video/')) {
        i = 1;
    }
    return props.medias.filter((media, index) => index !== i);
});
</script>

<template>
    <div :class="['rounded-lg overflow-hidden py-2', medias.length === 1 ? 'gallery full' : 'gallery grid']">
        <template v-if="medias.length === 1" >
            <div v-if="isVideo(medias[0])" class="video-container">
                <video
                    controls
                    :src="medias[0].original_url"
                    class="w-full h-80"
                    @click.stop="previewMedia(medias)"
                ></video>
            </div>

            <img v-else
                :src="medias[0].original_url"
                :alt="medias[0].name"
                :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32' : 'w-full h-80']"
                @click.stop="previewMedia(medias[0])"
            />
        </template>

        <template v-else-if="medias.length === 3" class="grid grid-cols-3 gap-y-0.5">
            <div class="col-span-1">
                <div v-for="(media, index) in [filteredImages[0]]" :key="index">
                    <img
                        :src="media.preview_url"
                        alt="Media"
                        class="w-full h-80 object-cover"
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
                            @click.stop="previewMedia(medias)"
                        ></video>
                    </div>
                    <img v-else
                         :src="media.preview_url"
                         :alt="media.name"
                         :class="['hover:opacity-90 cursor-pointer object-cover', small === true ? 'h-32' : 'w-full h-40']"
                         @click.stop="previewMedia(medias)"
                    />
                </div>
            </div>
        </template>

        <template v-else>
            <div
                v-for="(media, index) in medias.slice(0, 4)"
                :key="media.id"
                class="relative"
            >
                <div v-if="isVideo(media)" class="video-container">
                    <video
                        controls
                        :src="media.original_url"
                        class="w-full h-full object-cover"
                        @click.stop="previewMedia(medias)"
                    ></video>
                </div>
                <img v-else
                    :src="media.preview_url"
                    :alt="media.name"
                     :class="['hover:opacity-90 cursor-pointer bg-center bg-cover', small === true ? 'h-32' : 'w-full h-40']"
                    @click.stop="previewMedia(medias)"
                />
                <!-- Overlay untuk gambar lebih dari 4 -->
                <div
                    v-if="index === 3 && medias.length > 4"
                    class="absolute inset-0 more-overlay hover:opacity-70 cursor-pointer bg-center bg-cover"
                    @click.stop="previewMedia(medias)"
                >
                    +{{ medias.length - 4 }}
                </div>
            </div>
        </template>
    </div>

    <Teleport to="body">
        <div
        v-if="isModalOpen"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
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
                <div class="flex items-center justify-center min-h-[200px] max-h-[80vh]">
                    <template v-if="carouselMedia[currentIndex]">
                        <div v-if="isVideo(carouselMedia[currentIndex])" class="w-full h-full">
                            <video
                                controls
                                :src="carouselMedia[currentIndex].original_url"
                                class="max-w-full max-h-[80vh] mx-auto"
                            ></video>
                        </div>
                        <img
                            v-else
                            :src="carouselMedia[currentIndex].original_url"
                            :alt="carouselMedia[currentIndex].name"
                            class="max-w-full max-h-[80vh] object-contain"
                        />
                    </template>
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

    <div
        v-if="isModalOpen"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
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
                <div class="flex items-center justify-center min-h-[200px] max-h-[80vh]">
                    <template v-if="carouselMedia[currentIndex]">
                        <div v-if="isVideo(carouselMedia[currentIndex])" class="w-full h-full">
                            <video
                                controls
                                :src="carouselMedia[currentIndex].original_url"
                                class="max-w-full max-h-[80vh] mx-auto"
                            ></video>
                        </div>
                        <img
                            v-else
                            :src="carouselMedia[currentIndex].original_url"
                            :alt="carouselMedia[currentIndex].name"
                            class="max-w-full max-h-[80vh] object-contain"
                        />
                    </template>
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
</template>

<style scoped>
.gallery {
    display: grid;
    gap: 4px;
}
.gallery.full {
    grid-template-columns: 1fr;
}
.gallery.grid {
    grid-template-columns: repeat(2, 1fr);
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
}

.hs-overlay[style*="display: none"] {
    opacity: 0;
    pointer-events: none;
}
</style>
