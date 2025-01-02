<script setup>
import {Link, usePage} from "@inertiajs/vue3";
import { LucideHome, Users, CircleUser, IdCard, MessageSquareMore, Settings, CircleHelp, SlidersHorizontal, LogOut } from "lucide-vue-next";
import {ref} from "vue";

const currentPath = ref(usePage().url)

function isActiveNav(path) {
    return currentPath.value === path
}

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
                <a class="flex-none rounded-xl text-xl inline-block font-semibold focus:outline-none focus:opacity-80" href="#" aria-label="Preline">
                    <img src="../../images/logo.png" alt="">
                </a>
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
                            <Link :href="route('profile-photos')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/profile-photos') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <CircleUser class="shrink-0 size-4" /> Profile Photos
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('profile-covers')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/profile-covers') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <CircleUser class="shrink-0 size-4" /> Profile Covers
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('post.index')" type="button" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/post') || isActiveNav('/post/create') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <MessageSquareMore class="shrink-0 size-4" /> Post Creation
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('post-moderation.index')" type="button" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/post-moderation') ? 'bg-blue-100 text-gray-800': 'text-white']" aria-expanded="true" aria-controls="users-accordion-child">
                                <MessageSquareMore class="shrink-0 size-4" /> Post Moderation
                            </Link>
                        </li>

                        <li>
                            <Link :href="route('user.public')" :class="['flex items-center gap-x-3.5 py-2 px-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100', isActiveNav('/user/public-account') ? 'bg-blue-100 text-gray-800': 'text-white']">
                                <IdCard class="shrink-0 size-4" /> Public Account
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
                                <SlidersHorizontal class="shrink-0 size-4" /> App Settings
                            </Link>
                        </li>
                        <li>
                            <button type="button" class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100" aria-expanded="true" aria-controls="users-accordion-child">
                                <CircleHelp class="shrink-0 size-4" /> Supports
                            </button>
                        </li>

                        <li>
                            <button type="button" class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-white font-medium rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100" aria-expanded="true" aria-controls="users-accordion-child">
                                <Settings class="shrink-0 size-4" /> Settings
                            </button>
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
