<script setup>
import {Head, Link} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";

const props = defineProps({
    messages: Object
});

</script>

<template>
    <Head title="Messages" />
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1">
            <Link :href="route('message.show', 1)" class="shrink-0 group block p-3 hover:bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Mark Wanner</h3>
                        <p class="text-sm font-medium text-gray-400 dark:text-neutral-500">Messages Here</p>
                    </div>
                </div>
            </Link>
            <Link :href="route('message.show', 1)" class="shrink-0 group block p-3 hover:bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Someone</h3>
                        <p class="text-sm font-medium text-gray-400 dark:text-neutral-500">Messages Here</p>
                    </div>
                </div>
            </Link>
        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
