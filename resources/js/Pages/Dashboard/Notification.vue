<script setup>
import moment from "moment/moment.js";
import Pagination from "@/Components/Pagination.vue";
import {Head, Link, router} from "@inertiajs/vue3";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ChevronRight} from "lucide-vue-next";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineProps({
    notifications: {}
})

const readNotification = (item) => {
    router.visit(route('read-notification', item.id), {
        preserveScroll: true,
        method: 'post',
        onSuccess: visit => {
            router.get(item.data.url)
        },
    })
}
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Notifications
            </li>
        </Breadcrumbs>

        <!-- Card -->
        <div class="flex flex-col w-3/4">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center w-1/12">
                                    <div class="flex items-center justify-center">
                                        <span class="text-sm font-semibold">
                                          No
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start" colspan="2">
                                    <div class="flex items-center gap-x-2">
                                                <span class="text-sm font-semibold tracking-wide text-gray-800 dark:text-neutral-200">
                                                  Notifications
                                                </span>
                                    </div>
                                </th>

                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            <tr v-for="(notification, index) in notifications.data" key="user.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                <td class="size-px whitespace-nowrap text-center">
                                    <span class="text-xs">
                                        {{ index+1 }}
                                    </span>
                                </td>
                                <td class="size-px whitespace-nowrap align-top justify-between">
                                    <a @click="readNotification(notification)" class="block p-2 hover:bg-blue-100 dark:bg-neutral-900 dark:hover:bg-neutral-800" href="#">
                                        <span class="text-sm text-gray-800 dark:text-neutral-400">{{ notification.data.message }}</span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap text-end pe-3">
                                    <span class="text-xs text-gray-400">
                                        {{ moment(notification.created_at).fromNow() }}
                                    </span>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="notifications.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>/
</template>

<style scoped>

</style>
