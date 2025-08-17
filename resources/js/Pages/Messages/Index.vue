<script setup>
import {Head, Link, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import { onMounted, ref, reactive } from "vue";
import { FileImage } from "lucide-vue-next";

const props = defineProps({
    conversations: Object
});

const { getUnreadCountForConversation } = useUnreadMessages();
const unreadCounts = reactive({});
const conversations = reactive(props.conversations);
const currentUser = usePage().props.auth.user.id;

const truncatedText = (originalText) => {
    return originalText.length > 50
        ? originalText.slice(0, 50) + "..."
        : originalText;
}
onMounted(() => {
    Echo.private(`message.notification`)
        .listen('NewMessage', (event) => {
            if (event.user_ids && Array.isArray(event.user_ids)) {
                event.user_ids.forEach((userId) => {
                    const userConversation = conversations.find((c) => c.user_id === userId);
                    if (userConversation && event.message) {
                        userConversation.latest_message = [event.message];
                        userConversation.unread_messages_count++;
                    }
                });
            }
        })
        .error((error) => {
            console.log(error)
        })
});

</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3 flex justify-between items-center">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
            <Link :href="route('user.search')" type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                New Message
            </Link>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1">
            <Link :href="route('message.show', user.user_id)" v-for="user in conversations"
                  class="shrink-0 group block p-2 hover:bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full object-cover" :src="user.avatar" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3 flex-grow">
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-sm text-gray-800 dark:text-white">{{ user.name }}</h3>
                        </div>
                        <div v-if="user.latest_message.length > 0"
                           :class="['text-sm dark:text-neutral-500 font-medium text-gray-400', user.unread_messages_count > 0 ? 'flex justify-between' : '']">
                            <p v-if="user.latest_message[0].content" v-html="truncatedText(user.latest_message[0].content)"></p>
                            <p v-else class="flex items-center">
                                <FileImage class="shrink-0 size-4 mr-1" />
                                <span v-if="user.latest_message[0].sender_id === currentUser">You have sent a file</span>
                                <span v-else>You have received a file </span>
                            </p>
                            <span v-if="user.unread_messages_count > 0" class="inline-flex items-center py-0.5 px-1.5 rounded-full text-xs bg-red-500 text-white">
                                {{ user.unread_messages_count }} 
                            </span>
                        </div>
                        <p v-else class="text-sm font-medium text-gray-400 dark:text-neutral-500">No message</p>
                    </div>
                </div>
            </Link>
        </div>

    </HomeLayout>
</template>

<style scoped>

</style>