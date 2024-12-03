<script setup>
defineProps({
    medias: {
        type: Array,
        required: true,
    },
});

const previewMedia = (media) => {
    alert('here')
}

const isVideo = (media) => {
    return media.mime_type.startsWith('video/');
}
</script>

<template>
    <div :class="['rounded-lg overflow-hidden py-2', medias.length === 1 ? 'gallery full' : 'gallery grid']">
        <!-- Jika hanya satu gambar -->
        <template v-if="medias.length === 1">
            <div v-if="isVideo(medias[0])" class="video-container">
                <video
                    controls
                    :src="medias[0].original_url"
                    class="w-full h-auto"
                ></video>
            </div>
            <img v-else
                :src="medias[0].original_url"
                :alt="medias[0].name"
                class="w-full h-80 hover:opacity-90 cursor-pointer"
                @click.stop="previewMedia(medias[0])"
            />
        </template>

        <!-- Jika lebih dari satu gambar -->
        <template v-else>
            <div
                v-for="(media, index) in medias.slice(0, 4)"
                :key="media.id"
                class="relative"
            >
                <div v-if="isVideo(media)" class="video-container">
                    <video
                        controls
                        :src="media.preview_url"
                        class="w-full h-full object-cover"
                    ></video>
                </div>
                <img v-else
                    :src="media.preview_url"
                    :alt="media.name"
                    class="w-full h-40 object-cover hover:opacity-90 cursor-pointer"
                    @click.stop="previewMedia(media)"
                />
                <!-- Overlay untuk gambar lebih dari 4 -->
                <div
                    v-if="index === 3 && medias.length > 4"
                    class="absolute inset-0 more-overlay hover:opacity-70 cursor-pointer"
                    @click.stop="previewMedia(media)"
                >
                    +{{ medias.length - 4 }}
                </div>
            </div>
        </template>
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
    height: auto;
    border-radius: 8px;
}
</style>
