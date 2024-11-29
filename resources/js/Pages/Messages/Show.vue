<script setup>
import {Head} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    messages: Object
});

function textareaAutoHeight(el, offsetTop = 0) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight + offsetTop}px`;
}

onMounted(() => {
    const textareas = ['#hs-textarea-ex-1'];
    const cleanupFunctions = [];

    textareas.forEach((selector) => {
        const textarea = document.querySelector(selector);

        if (!textarea) return;

        const overlay = textarea.closest('.hs-overlay');

        const adjustHeight = () => textareaAutoHeight(textarea, 3);

        if (overlay) {
            const { element } = HSOverlay.getInstance(overlay, true);

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
    <Head title="Messages" />
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1">
            <div class="shrink-0 group block p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Mark Wanner</h3>
<!--                        <p class="text-sm font-medium text-gray-400 dark:text-neutral-500">Messages Here</p>-->
                    </div>
                </div>
            </div>

            <div class="py-3 px-4">
                <!-- Chat Bubble -->
                <ul class="space-y-5">
                    <!-- Chat -->
                    <li class="max-w-lg flex gap-x-2 sm:gap-x-4">
                        <!-- Card -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
                            <p class="text-sm text-gray-800 dark:text-white">
                                You can ask questions like:
                            </p>
                        </div>
                        <!-- End Card -->
                    </li>
                    <!-- End Chat -->

                    <!-- Chat -->
                    <li class="max-w-lg ms-auto flex justify-end gap-x-2 sm:gap-x-4">
                        <div class="grow text-end space-y-3">
                            <!-- Card -->
                            <div class="inline-block bg-blue-600 rounded-2xl p-4 shadow-sm">
                                <p class="text-sm text-white">
                                    what's preline ui?
                                </p>
                            </div>
                            <!-- End Card -->
                        </div>
                    </li>
                    <!-- End Chat -->

                    <!-- Chat Bubble -->
                    <li class="max-w-lg flex gap-x-2 sm:gap-x-4">
                        <!-- Card -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
                            <p class="text-sm text-gray-800 dark:text-white">
                                Preline UI is an open-source set of prebuilt UI components based on the utility-first Tailwind CSS framework.
                            </p>

                        </div>
                        <!-- End Card -->
                    </li>
                    <!-- End Chat Bubble -->
                </ul>
                <!-- End Chat Bubble -->

                <hr class="my-3">

                <!-- Textarea -->
                <div class="relative">
                    <textarea id="hs-textarea-ex-1" class="p-4 pb-12 block w-full border-gray-200 rounded-lg
                        text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none
                         dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                              placeholder="Send message..."></textarea>

                    <!-- Toolbar -->
                    <div class="absolute bottom-px inset-x-px p-2 rounded-b-md bg-white dark:bg-neutral-900">
                        <!-- Send Button -->
                        <button type="button" class="rounded-lg bg-blue-700 text-sm text-white hover:bg-blue-500 px-4 py-1">
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
