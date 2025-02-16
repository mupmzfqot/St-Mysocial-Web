<script setup>

import {ref, watch} from "vue";
import {Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ChevronRight, Search, CheckCircle, MinusCircle } from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import Pagination from "@/Components/Pagination.vue";
import {debounce} from "lodash";

const props = defineProps({
    users: Object,
    searchTerm: String,
});

const search = ref(props.searchTerm);

watch(
    search, debounce(
        (q) => router.get(route('admin.index'), { search: q }, { preserveState: true }), 500
    )
);
const updateStatus = (id, status) => {
    router.post(route('admin.update-status', id), {
        'is_active': status !== 1,
    });
}
</script>

<template>
    <Head title="Admin List" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Admin List
            </li>
        </Breadcrumbs>

        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                        <!-- Header -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <!-- Input -->
                            <div class="sm:col-span-1">
                                <label for="hs-as-table-product-review-search" class="sr-only">Search</label>
                                <div class="relative">
                                    <input type="text" id="hs-as-table-product-review-search" name="search"
                                           class="py-2 px-3 ps-11 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                           placeholder="Search"
                                           @input="searchUser"
                                           v-model="search"
                                    >
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-4">
                                        <Search class="shrink-0 size-4 text-gray-400" />
                                    </div>
                                </div>
                            </div>
                            <!-- End Input -->
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          No.
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start w-2/6">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          User
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Account State
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Joined Date
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start w-[100px]">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Last Login
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                            Action
                                        </span>
                                    </div>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            <tr v-for="(user, index) in users.data" key="user.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                <td class="size-px whitespace-nowrap">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ users.from+index }}
                                        </span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap align-top">
                                    <a class="block p-6" href="#">
                                        <div class="flex items-center gap-x-3">
                                            <img :src="user.avatar" class="size-10 shrink-0 rounded-full" alt="avatar">
                                            <div class="grow">
                                                <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ user.name }}</span>
                                                <span class="block text-sm text-gray-500 dark:text-neutral-500">{{ user.email }}</span>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <a class="block p-6" href="#">
                                        <span v-if="user.is_active === 1" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                          <CheckCircle class="size-3" />Active
                                        </span>
                                        <span v-else-if="user.is_active === 0" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                                          <MinusCircle class="size-3" />Inactive
                                        </span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ user.created_date }}
                                        </span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ user.last_login }}
                                        </span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <button @click="updateStatus(user.id, user.is_active)" v-if="user.is_active === 0" type="button" class="block" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-ai-invoice-modal" data-hs-overlay="#hs-ai-invoice-modal">
                                        <span class="px-6 py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                            Activate
                                          </span>
                                        </span>
                                    </button>
                                    <button @click="updateStatus(user.id, user.is_active)" v-if="user.is_active === 1" type="button" class="block" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-ai-invoice-modal" data-hs-overlay="#hs-ai-invoice-modal">
                                        <span class="px-6 py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                            Deactivate
                                          </span>
                                        </span>
                                    </button>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="users.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
