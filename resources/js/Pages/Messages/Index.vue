<script setup>
import {Head, Link, router} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref} from "vue";
import { useUnreadMessages } from '@/Composables/useUnreadMessages';

const props = defineProps({
    conversations: Object
});

const { getUnreadCountForConversation } = useUnreadMessages();

const truncatedText = (originalText) => {
    return originalText.length > 50
        ? originalText.slice(0, 50) + "..."
        : originalText;
}

</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1">
            <Link :href="route('message.show', user.user_id)" v-for="user in conversations"
                  class="shrink-0 group block p-2 hover:bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" :src="user.avatar" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3 flex-grow">
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-sm text-gray-800 dark:text-white">{{ user.name }}</h3>
                            <span v-if="getUnreadCountForConversation(user.user_id) > 0"
                                  class="inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium bg-red-500 text-white">
                                {{ getUnreadCountForConversation(user.user_id) }}
                            </span>
                        </div>
                        <div v-if="user.latest_message.length > 0"
                           :class="['text-sm dark:text-neutral-500 font-medium', user.latest_message[0].is_read === false ? 'flex justify-between text-gray-700' : 'text-gray-400']">
                            <p v-html="truncatedText(user.latest_message[0].content)"></p>
                            <span v-if="user.latest_message[0].is_read === false" class="inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium bg-red-500 text-white ms-auto">
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
