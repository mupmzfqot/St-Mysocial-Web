<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { useForm } from "@inertiajs/vue3";
import { Heart, XCircle, X, PencilLine, SmilePlus, Paperclip } from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css';
import { TransitionRoot } from '@headlessui/vue';

const fileInput = ref(null);
const previews = ref([]);
const commentsContainer = ref(null);
const showCropModalVisible = ref(false);
const cropImageUrl = ref('');
const cropImageDefault = ref('');
const croppedFile = ref(null);
const cropImageType = ref('');
const cropImageIndex = ref(null);
const cropper = ref(null);

const props = defineProps({
    postId: {
        type: [Number, String],
        required: true
    },
    comments: {
        type: Array,
        default: () => []
    },
    currentUser: {
        type: Object,
        default: null
    },
    config: {
        type: Object,
        default: () => ({
            showLikeCount: true,
            showUserAvatar: true,
            allowFileUpload: true
        })
    },
    singlePost: false
});

const emit = defineEmits([
    'like-comment',
    'unlike-comment',
    'delete-comment',
    'comment-added'
]);

const form = useForm({
    message: '',
    post_id: props.postId,
    files: []
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const validateFile = (file) => {
    if (!ALLOWED_TYPES.includes(file.type)) {
        alert('File type not supported');
        return false;
    }
    return true;
}

const handleFiles = (event) => {
    const files = event.target?.files;
    if (files) {
        Array.from(files).forEach((file) => {
            if (file.size <= 5 * 1024 * 1024 && validateFile(file)) {
                const fileReader = new FileReader();
                fileReader.onload = (e) => {
                    previews.value.push({
                        url: e.target.result,
                        type: file.type,
                        name: file.name,
                        file: file
                    });
                };
                form.files.push(file);
                fileReader.readAsDataURL(file);
            } else {
                alert('File is too large. Maximum size is 5MB.');
            }
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

const submitComment = () => {
    if (!form.message.trim() && previews.value.length === 0) {
        alert('Please enter a comment or upload a file');
        return;
    }

    form.post(route('user-post.store-comment'), {
        onSuccess: () => {
            form.reset();
            previews.value = [];
            emit('comment-added');
        },
        onError: (errors) => {
            console.error('Comment submission error:', errors);
        }
    });

    nextTick(() => {
        if (commentsContainer.value) {
            commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight;
        }
    });
};

// Comment interaction methods
const handleCommentLike = (comment) => {
    emit(comment.is_liked ? 'unlike-comment' : 'like-comment', comment.id);
};

const handleDeleteComment = (commentId) => {
    emit('delete-comment', commentId);
};

const formatCommentTime = (timestamp) => {
    return new Date(timestamp).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const showEmojiPicker = ref(false);

const onSelectEmoji = (emoji) => {
    form.message += emoji.i;
    showEmojiPicker.value = false;
};

const showCropModal = (file, index) => {
    console.log(file)
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

onMounted(() => {
    cropper.value = ref('cropper')
})

</script>

<template>
    <div class="comment-container relative flex flex-col">
        <!-- Comments List (Scrollable) -->
        <div
            ref="commentsContainer"
            class="comments-list flex-grow overflow-y-auto pb-10 pr-2 mb-2
                [&::-webkit-scrollbar]:w-1
                [&::-webkit-scrollbar-track]:rounded-full
                [&::-webkit-scrollbar-track]:bg-gray-100
                [&::-webkit-scrollbar-thumb]:rounded-full
                [&::-webkit-scrollbar-thumb]:bg-gray-300
                dark:[&::-webkit-scrollbar-track]:bg-neutral-700
                dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500"
        >
            <!-- No Comments State -->
            <div
                v-if="comments.length === 0"
                class="text-gray-500 text-sm"
            >
                No comments yet
            </div>

            <!-- comment list -->
            <div class="flex gap-x-3 overflow-auto" v-for="comment in comments">
                <!-- Icon -->
                <div class="relative last:after:hidden after:absolute after:top-10 after:bottom-0 after:start-5 after:w-px after:-translate-x-[0.5px] after:bg-gray-200 dark:after:bg-neutral-700">
                    <div class="relative z-10 size-10 flex justify-center items-center">
                        <img class="shrink-0 size-10 rounded-full" :src="comment.user.avatar" alt="Avatar">
                    </div>
                </div>
                <!-- End Icon -->

                <!-- Right Content -->
                <div class="grow pt-0.5 pb-5">
                    <h3 class="flex gap-x-1.5 text-sm justify-between font-semibold text-gray-800 dark:text-white">
                        {{ comment.user.name }}
                        <p class="mt-1 text-xs italic text-gray-400 dark:text-neutral-400">
                            {{ comment.created_at }}
                        </p>
                    </h3>
                    <p class="mt-1 text-sm text-gray-800 dark:text-neutral-400" v-html="comment.message"></p>
                    <PostMedia
                        v-if="comment.media && comment.media.length > 0"
                        :medias="comment.media"
                        :small="true"
                    />
                    <div class="flex items-center mt-2 space-x-3">
                        <!-- Like Button -->
                        <button
                            @click="handleCommentLike(comment)"
                            class="flex items-center text-sm text-gray-600 hover:text-blue-600"
                        >
                            <Heart
                                class="w-4 h-4 mr-1"
                                :class="comment.is_liked ? 'text-red-500 fill-current' : 'text-gray-500'"
                            />
                            {{ comment.like_count }} Likes
                        </button>

                        <!-- Delete Button -->
                        <button
                            v-if="currentUser && comment.user_id === currentUser.id"
                            @click="handleDeleteComment(comment.id)"
                            class="text-sm text-red-500 hover:text-red-700 flex items-center"
                        >
                            <XCircle class="w-4 h-4 mr-1" />
                            Delete
                        </button>
                    </div>
                </div>
                <!-- End Right Content -->
            </div>
            <!-- End Item -->
        </div>

        <!-- Comment Input (Fixed at Bottom) -->
        <div class="z-[9999]" >
            <div
                v-if="currentUser"
                class="bg-gray-100 fixed bottom-0 left-0 right-0 dark:bg-neutral-900 shadow-lg p-4 border-t"
            >
                <!-- Chat Input Area -->
                <div class="flex items-center space-x-2" >

                    <img
                        :src="currentUser.avatar"
                        :alt="`${currentUser.name}'s avatar`"
                        class="w-10 h-10 rounded-full"
                    />

                    <!-- Chat Input -->
                    <input
                        id="chat-input"
                        v-model="form.message"
                        contenteditable="true"
                        accept="image/png, image/gif, image/jpeg, image/jpg"
                        class="flex-1 py-2 px-3 text-sm border rounded-lg border-gray-300 bg-white focus:ring-1 focus:ring-blue-500 focus:outline-none break-words"
                        placeholder="Type a message..."
                    >

                    <!-- Emoji Picker -->
                    <button @click="showEmojiPicker = !showEmojiPicker"  type="button" class="flex items-center text-gray-800 hover:text-blue-600">
                        <SmilePlus class="shrink-0 size-5" />
                    </button>

                    <EmojiPicker
                        v-if="showEmojiPicker"
                        @select="onSelectEmoji"
                        :class="['absolute bottom-16', singlePost ? 'right-1' : '']"
                    />

                    <!-- File Upload -->
                    <div v-if="config.allowFileUpload" class="flex items-center">
                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFiles"
                            multiple
                            accept="image/*,video/*,application/pdf"
                            class="hidden"
                        />
                        <button
                            @click="triggerFileInput"
                            class="flex items-center text-gray-600 hover:text-blue-600"
                        >
                            <Paperclip class="w-5 h-5 mr-1" />
                        </button>

                        <!-- Submit Button -->
                        <button
                            @click="submitComment"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-gray-200 text-sm text-gray-600 rounded-full hover:text-blue-600 hover:font-bold disabled:opacity-50"
                        >
                            {{ form.processing ? 'Sending...' : 'Send' }}
                        </button>
                    </div>

                </div>

                <!-- File Previews -->
                <div v-if="previews.length > 0" class="flex flex-wrap gap-2 mt-2 ps-12">
                    <div
                        v-for="(preview, index) in previews"
                        :key="index"
                        class="relative"
                    >
                        <img v-if="preview.type.startsWith('image/')"
                            :src="preview.url"
                            :alt="preview.name"
                            class="w-20 h-20 object-cover rounded-lg"
                        />
                        <img  v-else-if="preview.type === 'application/pdf'"
                            src="../../images/pdf-icon.svg"
                            :alt="preview.name"
                            class="w-20 h-20 object-cover rounded-lg"
                        />
                        <button
                            @click="removeMedia(index)"
                            class="absolute top-0 -right-2 bg-red-500 text-white rounded-full p-1" title="remove media"
                        >
                            <X class="w-3 h-3" />
                        </button>
                        <button v-if="preview.type.startsWith('image/')"
                            @click="showCropModal(preview, index)"
                            class="absolute -top-0.5 right-4 bg-orange-500 text-white rounded-full p-1" title="crop image"
                        >
                            <PencilLine class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <TransitionRoot
        :show="showCropModalVisible"
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
        class="z-[9999]"
        >
        <div class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg shadow-lg p-4 w-full">
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

</style>
