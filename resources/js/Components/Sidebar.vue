<script setup>
import {Link, usePage} from "@inertiajs/vue3";
import { LucideHome, Users, CircleUser, IdCard, MessageSquareMore, Settings, CircleHelp, SlidersHorizontal } from "lucide-vue-next";
import {onMounted, ref} from "vue";

const currentPath = ref(usePage().url)
const pendingUserApprovals = ref(0)
const pendingPostApprovals = ref(0)

function isActiveNav(path) {
    return currentPath.value === path
}

async function fetchPendingApprovals() {
    try {
        const response = await axios.get(route('admin.pending-approval'))
        pendingUserApprovals.value = response.data.pendingUsers;
        pendingPostApprovals.value = response.data.pendingPosts;
        return response.data;
    } catch (error) {
        console.error('Error fetching pending approvals:', error)
    }
}

onMounted(fetchPendingApprovals)

</script>

<template>
    <!-- Sidebar -->
    <div id="hs-application-sidebar" class="hs-overlay  [--auto-close:lg] bg-gradient-blue-reverse
      hs-overlay-open:translate-x-0
      -translate-x-full transition-all duration-300 transform
      w-[260px] h-full
      hidden
      fixed inset-y-0 start-0 z-[60]
      bg-white border-e border-gray-200
      lg:block lg:translate-x-0 lg:end-auto lg:bottom-0
     " role="dialog" tabindex="-1" aria-label="Sidebar">
        <div class="relative flex flex-col h-full max-h-full">
            <div class="px-6 pt-4">
                <!-- Logo -->
                <Link :href="route('dashboard')" class="flex-none rounded-xl text-xl inline-block font-semibold focus:outline-none focus:opacity-80" aria-label="TeamST">
                    <img src="../../images/logo.png" class="h-10 -mt-1.5" alt="">
                </Link>
                <!-- End Logo -->
            </div>

            <!-- Content -->
            <div class="h-full overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300">
                <nav class="hs-accordion-group p-3 w-full flex flex-col flex-wrap" data-hs-accordion-always-open>
                    <ul class="flex flex-col space-y-1">
                        <li>
                            <span class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium">
                                General
                            </span>
                        </li>
                        <li>
                            <Link :href="route('dashboard')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/dashboard') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <LucideHome class="shrink-0 size-4" /> Dashboard
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('admin.index')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/admin') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <Users class="shrink-0 size-4" /> Admin List
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('user.index')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/user') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <Users class="shrink-0 size-4" /> User List
                            </Link>
                        </li>

                        <hr class="pb-2">

                        <li>
                            <span class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium">
                                Moderation
                            </span>
                        </li>

                        <li>
                            <Link :href="route('post.index')" type="button" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/post/index') || isActiveNav('/post/create') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <MessageSquareMore class="shrink-0 size-4" /> Post Creation
                            </Link>
                        </li>

                        <li class="hs-accordion" id="account-accordion">
                            <button type="button" class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100" aria-expanded="true" aria-controls="account-accordion-child">
                                <MessageSquareMore class="shrink-0 size-4" /> Post Moderation

                                <svg class="hs-accordion-active:block ms-auto hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>

                                <svg class="hs-accordion-active:hidden ms-auto block size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div id="account-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 hidden" role="region" aria-labelledby="account-accordion">
                                <ul class="ps-8 pt-1 space-y-1">
                                    <li>
                                        <Link :href="route('post-moderation.index-st')" type="button" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/post-moderation/st') ? 'bg-blue-100 text-gray-800': 'text-white']" aria-expanded="true" aria-controls="users-accordion-child">
                                            ST Post
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('post-moderation.index')" type="button" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/post-moderation') ? 'bg-blue-100 text-gray-800': 'text-white']" aria-expanded="true" aria-controls="users-accordion-child">
                                            Public Post
                                            <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full ms-auto text-xs font-medium bg-gray-100 text-red-800 dark:bg-white/10 dark:text-white">
                                                {{ pendingPostApprovals }}
                                            </span>
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <li>
                            <Link :href="route('user.public')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/user/public-account') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <IdCard class="shrink-0 size-4" /> Public Account
                                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full ms-auto text-xs font-medium bg-gray-100 text-red-800 dark:bg-white/10 dark:text-white">
                                    {{ pendingUserApprovals }}
                                </span>
                            </Link>
                        </li>

                        <hr class="pb-2">

                        <li>
                            <span class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium">
                                Setting and Others
                            </span>
                        </li>

                        <li>
                            <Link :href="route('app-setting')" class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100">
                                <SlidersHorizontal class="shrink-0 size-4" /> Settings
                            </Link>
                        </li>

                      </ul>
                </nav>
            </div>
            <!-- End Content -->
        </div>
    </div>
    <!-- End Sidebar -->
</template>

<style scoped>

</style>
