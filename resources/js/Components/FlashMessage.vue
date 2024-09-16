<script setup>
import {CheckCircle, XCircle} from "lucide-vue-next";

import { ref, onMounted } from 'vue';

const props = defineProps({
    message: String,
    isVisible: false,
});

const isVisible = ref(props.isVisible);

console.log(props.message);
console.log(props.isVisible);

const hideMessage = () => {
    isVisible.value = false;
}

onMounted(() => {
    isVisible.value = true;
});

</script>

<template>
    <!-- Flash message -->
    <div class="absolute top-3 end-5" style="z-index: 1000">

<!--        <div id="dismiss-alert" v-if="$page.props.flash.error" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-red-50  border-2 border-red-500 rounded-lg p-4 dark:bg-red-800/30 dark:bg-teal-800/10 dark:border-red-900 dark:text-red-500" role="alert" tabindex="-1" aria-labelledby="hs-dismiss-button-label">-->
<!--            <div class="flex">-->
<!--                <div class="shrink-0">-->
<!--                    <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800 dark:border-red-900 dark:bg-red-800 dark:text-red-400">-->
<!--                        <CheckCircle class="shrink-0 size-4" />-->
<!--                    </span>-->
<!--                </div>-->
<!--                <div class="ms-3">-->
<!--                    <h3 id="hs-dismiss-button-label" class="text-gray-800 font-semibold dark:text-white">-->
<!--                        Success.-->
<!--                    </h3>-->
<!--                    <p class="text-sm text-red-700 dark:text-neutral-400">-->
<!--                        {{ $page.props.flash.error }}-->
<!--                    </p>-->
<!--                </div>-->
<!--                <div class="ps-3 ms-auto">-->
<!--                    <div class="-mx-1.5 -my-1.5">-->
<!--                        <button type="button" class="inline-flex bg-red-50 rounded-lg p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:bg-red-100 dark:bg-transparent dark:text-red-600 dark:hover:bg-red-800/50 dark:focus:bg-red-800/50" data-hs-remove-element="#dismiss-alert">-->
<!--                            <span class="sr-only">Dismiss</span>-->
<!--                            <XCircle class="h-5 w-5 text-red-500 dark:text-red-500" />-->
<!--                        </button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <div v-show="isVisible" class="bg-teal-50  border-2 border-teal-500 rounded-lg p-4 dark:bg-teal-800/30 dark:bg-teal-800/10 dark:border-teal-900 dark:text-teal-500" role="alert" tabindex="-1">
            <div class="flex">
                <div class="shrink-0">
                <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-teal-100 bg-teal-200 text-teal-800 dark:border-teal-900 dark:bg-teal-800 dark:text-teal-400">
                    <CheckCircle class="shrink-0 size-4" />
                </span>
                </div>
                <div class="ms-3">
                    <h3 class="text-gray-800 font-semibold dark:text-white">
                        Success.
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-neutral-400">
                        {{ message }}
                    </p>
                </div>
                <div class="ps-3 ms-auto">
                    <div class="-mx-1.5 -my-1.5">
                        <button @click="hideMessage" type="button" class="inline-flex bg-teal-50 rounded-lg p-1.5 text-teal-500 hover:bg-teal-100 focus:outline-none focus:bg-teal-100 dark:bg-transparent dark:text-teal-600 dark:hover:bg-teal-800/50 dark:focus:bg-teal-800/50">
                            <span class="sr-only">Dismiss</span>
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- End flash message -->
</template>

<style scoped>

</style>
