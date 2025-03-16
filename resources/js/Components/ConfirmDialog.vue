<script setup>
import { AlertCircle } from "lucide-vue-next";
import { router } from "@inertiajs/vue3";
import { usePendingApprovals } from "@/Composables/usePendingApprovals.js";

const { data } = defineProps({
    data: Object,
})
const { fetchPendingApprovals } = usePendingApprovals();
const isConfirmed = async () => {
    router.post(data.url, data.data || {}, {
        onSuccess: async () => {
            await fetchPendingApprovals();

            // Call any additional onSuccess callback if provided
            if (data.onSuccess && typeof data.onSuccess === 'function') {
                await data.onSuccess();
            }

            HSOverlay.close('#confirm-dialog');
        },
    });

}

</script>

<template>

    <div id="confirm-dialog" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-scale-animation-modal-label">
        <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
            <div class="w-full flex flex-col bg-white border shadow-sm rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
                <div class="p-4 overflow-y-auto">
                    <div class="flex">
                        <div class="shrink-0">
                            <AlertCircle class="shrink-0 size-7" color="orange" />
                        </div>
                        <div class="ms-3 mt-0.5">
                            <h2 id="hs-discovery-label" class="text-gray-800 font-semibold dark:text-white">
                                Warning!
                            </h2>
                            <p class="mt-2 text-sm text-gray-700 dark:text-neutral-400" v-html="data.message">
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700" data-hs-overlay="#confirm-dialog">
                        Cancel
                    </button>
                    <button @click="isConfirmed()" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Yes, Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
