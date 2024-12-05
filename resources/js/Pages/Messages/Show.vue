<script setup>
import {Head, router} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref, onMounted, onBeforeUnmount, watch, nextTick} from 'vue';

const props = defineProps({
    messages: Object,
    user: Object,
    conversation: Object,
});

const content = ref('');
const activeMessages = ref([...props.messages]);
const isFriendTyping = ref(false);

const sendMessage = async (conversationId) => {

    if (content.value.trim() !== "") {
        await axios
            .post(route('message.send', conversationId), {
                message: content.value,
            })
            .then((response) => {
                content.value = "";
            });
    }
}

function textareaAutoHeight(el, offsetTop = 0) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight + offsetTop}px`;
}

onMounted(() => {

    Echo.private(`conversation.${props.conversation.id}`).listen('MessageSent', (event) => {
        activeMessages.value.push(event);
    });

    const textareas = ['#hs-textarea-ex-1'];
    const cleanupFunctions = [];

    textareas.forEach((selector) => {
        const textarea = document.querySelector(selector);

        if (!textarea) return;

        const overlay = textarea.closest('.hs-overlay');

        const adjustHeight = () => textareaAutoHeight(textarea, 3);

        if (overlay) {
            const {element} = HSOverlay.getInstance(overlay, true);

            if (element) {
                element.on('open', adjustHeight);
                cleanupFunctions.push(() => element.off('open', adjustHeight));
            }
        } else {
            adjustHeight();
        }

        textarea.addEventListener('input', adjustHeight);
        cleanupFunctions.push(() => textarea.removeEventListener('input', adjustHeight));
    });

    onBeforeUnmount(() => {
        cleanupFunctions.forEach((cleanup) => cleanup());
    });
});


</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1">
            <div class="shrink-0 group block p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" :src="user.avatar" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ user.name }}</h3>
                        <p v-if="isFriendTyping" class="text-sm -mt-1 text-gray-400 dark:text-neutral-500">
                            typing...
                        </p>
                    </div>
                </div>
            </div>

            <div class="py-3 px-4">
                <!-- Chat Bubble -->
                <ul class="space-y-2" v-if="activeMessages.length > 0" id="messagesContainer">
                    <li :class="['max-w-md flex gap-x-2 sm:gap-x-4', message.sender_id === $page.props.auth.user.id ? 'justify-end ms-auto' : '']"
                        v-for="message in activeMessages" :key="message.id">
                        <div class="bg-blue-600 rounded-2xl px-4 py-2 space-y-3" v-if="message.sender_id === $page.props.auth.user.id">
                            <p class="text-sm text-white" v-html="message.content"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 space-y-3" v-else>
                            <p class="text-sm text-gray-800" v-html="message.content"></p>
                        </div>
                    </li>
                </ul>

                <div v-else class="text-sm text-gray-400 dark:text-white py-3">
                    <p>No message.</p>
                </div>


                <!-- End Chat Bubble -->

                <hr class="my-3">

                <!-- Textarea -->
                <div class="relative">
                    <textarea id="hs-textarea-ex-1" @input="sendTypingEvent" v-model="content" class="p-4 pb-12 block w-full border-gray-200 rounded-lg
                        text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none
                         dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                              placeholder="Send message..."></textarea>

                    <!-- Toolbar -->
                    <div class="absolute bottom-px inset-x-px p-2 rounded-b-md bg-white dark:bg-neutral-900">
                        <!-- Send Button -->
                        <button type="button"
                                class="rounded-lg bg-blue-600 text-sm text-white hover:bg-blue-500 px-4 py-1"
                                @click="sendMessage(conversation.id)">
                            Send
                        </button>
                        <!-- End Send Button -->
                    </div>
                    <!-- End Toolbar -->
                </div>
                <!-- End Textarea -->
            </div>

        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
