<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import { ChevronRight, UsersRound, MessageSquare, Rss, MessageSquareMore, HandHeart, File, FileChartLine, UserRoundCheck } from "lucide-vue-next";

const props = defineProps({
    users: Object,
    totalAccounts: Number,
    totalActiveAccounts: Number,
    totalBlockedAccounts: Number,
    totalPosts: Number,
    totalActivePosts: Number,
    totalComments: Number,
    totalLikes: Number,
    totalMessages: Number,
    totalPhotos: Number,

});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Dashboard
            </li>
        </Breadcrumbs>

        <!-- Card Section -->
        <div class="max-w-full">
            <!-- Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Card -->
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500">
                                Total users
                            </p>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl sm:text-xl font-medium text-gray-800 dark:text-neutral-200">
                                    {{ users.user_count + users.public_user_count }}
                                </h3>
                            </div>
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <UsersRound class="shrink-0 size-5" />
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500">
                                Total Comments
                            </p>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="mt-1 text-xl font-medium text-gray-800 dark:text-neutral-200">
                                    {{ totalComments }}
                                </h3>
                            </div>
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <MessageSquareMore class="shrink-0 size-5" />
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500">
                                Total Messages
                            </p>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-neutral-200">
                                    {{ totalMessages }}
                                </h3>
                            </div>
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <MessageSquare class="shrink-0 size-5" />
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500">
                                Total Posts
                            </p>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="mt-1 text-xl font-medium text-gray-800 dark:text-neutral-200">
                                    {{ totalPosts }}
                                </h3>
                            </div>
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <Rss class="shrink-0 size-5" />
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <Link :href="route('most-user-posts')" class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div class="flex items-center gap-x-2 text-wrap text-xl font-medium text-gray-800 dark:text-neutral-200">
                            Individual with most posting
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <FileChartLine class="shrink-0 size-5" />
                        </div>
                    </div>
                </Link>
                <!-- End Card -->

                <!-- Card -->
                <Link :href="route('most-comment-posts')" class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div class="flex items-center gap-x-2 text-wrap text-xl font-medium text-gray-800 dark:text-neutral-200">
                            Most Comment Posts
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <MessageSquareMore class="shrink-0 size-5" />
                        </div>
                    </div>
                </Link>
                <!-- End Card -->

                <!-- Card -->
                <Link :href="route('most-liked-posts')" class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div class="flex items-center gap-x-2 text-wrap text-xl font-medium text-gray-800 dark:text-neutral-200">
                            Most liked posts
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <HandHeart class="shrink-0 size-5" />
                        </div>
                    </div>
                </Link>
                <!-- End Card -->

                <!-- Card -->
                <Link :href="route('active-users')" class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                    <div class="p-4 md:p-5 flex justify-between gap-x-3">
                        <div class="flex items-center gap-x-2 text-wrap text-xl font-medium text-gray-800 dark:text-neutral-200">
                            Active users
                        </div>
                        <div class="shrink-0 flex justify-center items-center size-[46px] bg-blue-600 text-white rounded-full dark:bg-blue-900 dark:text-blue-200">
                            <UserRoundCheck class="shrink-0 size-5" />
                        </div>
                    </div>
                </Link>
                <!-- End Card -->
            </div>
            <!-- End Grid -->
        </div>
        <!-- End Card Section -->


        <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center border-b rounded-t-xl py-3 px-4 md:px-5 dark:border-neutral-700">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    Full Statistics
                </h3>
            </div>
            <div class="border overflow-hidden dark:border-neutral-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead>
                    <tr>
                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-500">Name</th>
                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-500">Count</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Admin and User</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalAccounts }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                            <Link :href="route('account', 1)" class="hover:text-blue-600">Active Accounts</Link>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                            <Link :href="route('account', 1)" class="hover:text-blue-600">{{ totalActiveAccounts }}</Link>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                            <Link :href="route('account', 0)" class="hover:text-blue-600">Blocked Accounts</Link>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                            <Link :href="route('account', 0)" class="hover:text-blue-600">{{ totalBlockedAccounts }}</Link>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Total Comments</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalComments }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Total Likes</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalLikes }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Total Photos</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalPhotos }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Total Posts</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalPosts }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">Total Messages</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ totalMessages }}</td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
