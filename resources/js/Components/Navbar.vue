<script setup>
import {UserIcon, LogOut, UserCircle, CircleCheckBig} from "lucide-vue-next";
import {Link, router, usePage} from "@inertiajs/vue3";

const { unreadNotifications: notifications } = usePage().props;

const readNotification = (item) => {
    router.post(route('read-notification', item.id), {}, {preserveScroll: true})
    router.visit(route('read-notification', item.id), {
        preserveScroll: true,
        method: 'post',
        onFinish: visit => {
            router.get(item.data.url)
        }
    })
}

</script>

<template>
    <!-- ========== HEADER ========== -->
    <header class="sticky top-0 inset-x-0 flex flex-wrap md:justify-start md:flex-nowrap z-[48] w-full bg-white border-b text-sm py-2.5 lg:ps-[260px] bg-primary-gradient-reverse">
        <nav class="px-4 sm:px-6 flex basis-full items-center w-full mx-auto">
            <div class="me-5 lg:me-0 lg:hidden">
                <!-- Logo -->
                <Link :href="route('dashboard')" class="flex-none rounded-md text-xl inline-block font-semibold focus:outline-none focus:opacity-80" aria-label="TeamST">
                    <img src="../../images/logo.png" class="h-10" alt="">
                </Link>
                <!-- End Logo -->
            </div>

            <div class="w-full flex items-center justify-end ms-auto md:justify-between gap-x-1 md:gap-x-3">

                <div class="hidden md:block">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-3.5">
                            <svg class="shrink-0 size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <input type="text" class="py-2 ps-10 pe-16 block w-full bg-white border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Search">
                        <div class="hidden absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-1">
                            <button type="button" class="inline-flex shrink-0 justify-center items-center size-6 rounded-full text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600" aria-label="Close">
                                <span class="sr-only">Close</span>
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                            </button>
                        </div>
                    </div>
                    <!-- End Search Input -->
                </div>

                <div class="flex flex-row items-center justify-end gap-1">
                    <button type="button" class="md:hidden size-[38px] relative inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <span class="sr-only">Search</span>
                    </button>

                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                        <button type="button" id="hs-dropdown-with-dividers" class="hs-dropdown-toggle relative inline-flex justify-center items-center size-[38px] text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            <span class="sr-only">Notifications</span>
                            <span class="absolute top-0 end-0 inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium transform -translate-y-1/2 translate-x-1/2 bg-red-500 text-white">
                                {{ notifications.length > 99 ? '99+' : notifications.length }}
                            </span>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] w-[350px] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md border border-gray-200 rounded-lg mt-2 divide-y divide-gray-200 dark:bg-neutral-800 dark:border dark:border-neutral-700 dark:divide-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-with-dividers">
                            <div class="p-1 space-y-0.5">
                                <p class="flex items-center gap-x-3.5 py-2 px-3 font-semibold text-gray-800">
                                    Notifications
                                </p>
                            </div>
                            <div class="p-1 space-y-0.5">
                                <a href="#" @click="readNotification(notif)" v-for="notif in notifications.slice(0, 9)" class="flex items-center gap-x-1 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                    {{ notif.data.message }}
                                </a>

                            </div>
                            <div class="p-1 space-y-0.5">
                                <Link :href="route('notifications')" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                    <CircleCheckBig class="shrink-0 size-4 text-green-700" /> Mark all as read
                                </Link>
                            </div>

                        </div>
                    </div>

                    <!-- Dropdown -->
                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex ms-2">
                        <button id="hs-dropdown-custom-trigger" type="button" class="hs-dropdown-toggle py-1 ps-1 pe-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-full border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                            <img class="w-8 h-auto rounded-full object-cover" :src="$page.props.auth.user.avatar" alt="Avatar">
                            <svg class="hs-dropdown-open:rotate-180 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 border border-gray-200 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 dark:bg-neutral-800 dark:border dark:border-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-custom-trigger">
                            <div class="py-3 px-5 bg-gray-100 rounded-t-lg dark:bg-neutral-700">
                                <p class="text-sm text-gray-500 dark:text-neutral-500">Signed in as</p>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $page.props.auth.user.name }}</p>
                            </div>
                            <div class="p-1 space-y-0.5">
                                <Link :href="route('profile.edit')" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    <UserIcon class="shrink-0 size-4" />Profile
                                </Link>
                                <Link :href="route('logout')" as="button" method="post" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    <LogOut class="shrink-0 size-4" />Log Out
                                </Link>
                            </div>
                        </div>
                    </div>
                    <!-- End Dropdown -->

                </div>
            </div>
        </nav>

    </header>
    <!-- ========== END HEADER ========== -->

</template>

<style scoped>

</style>
