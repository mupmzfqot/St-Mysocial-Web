<script setup>
import { ref, nextTick } from 'vue';
import { useForm } from "@inertiajs/vue3";
import { Heart, XCircle, X, SmilePlus, Paperclip } from "lucide-vue-next";
import PostMedia from "@/Components/PostMedia.vue";
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';

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

const fileInput = ref(null);
const previews = ref([]);
const commentsContainer = ref(null);

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFiles = (event) => {
    const files = event.target?.files;
    if (files) {
        Array.from(files).forEach((file) => {
            if (file.size <= 5 * 1024 * 1024) {
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
                        class="w-10 h-10 rounded-full v-"
                    />

                    <!-- Chat Input -->
                    <input
                        id="chat-input"
                        v-model="form.message"
                        contenteditable="true"
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
                            class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
