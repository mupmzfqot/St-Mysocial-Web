<script setup>
import {Head, Link, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ChevronRight, Image, Earth } from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ref} from "vue";

const fileInput = ref(null);
const previews = ref([]);

const form = useForm({
    content: '',
    files: [],
    type: 'st',
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFiles = (event) => {
    const files = event.target.files;
    previews.value = [];
    form.files = [];

    Array.from(files).forEach((file) => {
        const fileReader = new FileReader();
        fileReader.onload = (e) => {
            previews.value.push({
                url: e.target.result,
                type: file.type,
            });
        };
        form.files.push(file);
        fileReader.readAsDataURL(file);
    });
};

const submit = () => {
    form.post(route('post.store'), {
        onSuccess: () => {
            console.log('Upload successful');
            form.reset();
            previews.value = [];
        },
        onError: (errors) => {
            console.error('Upload failed:', errors);
        },
    });
};

</script>

<template>
    <Head title="Create New Post" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <a class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </a>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center">
                <Link :href="route('post.index')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Posts
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Create
            </li>
        </Breadcrumbs>

        <!-- Card Section -->
        <div class="w-full">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-neutral-800">
                <div class="mb-3">
                    <label class="text-gray-600" v-if="previews.length > 0">Media preview</label>
                    <div class="grid grid-cols-none sm:grid-cols-4 gap-2 mt-3">

                        <div
                            v-for="(file, index) in previews"
                            :key="index"
                            class="relative w-full overflow-hidden rounded-lg space-y-2"
                        >
                            <img v-if="file.type.startsWith('image/')" :src="file.url" alt="Preview" class="w-full object-cover max-h-80" />
                            <video v-else-if="file.type.startsWith('video/')" controls class="aspect-video rounded-lg">
                                <source :src="file.url" :type="file.type" />
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <!-- Textarea -->
                        <div class="relative">
                        <textarea id="hs-textarea-ex-1" v-model="form.content"
                                  class="p-4 pb-12 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                  placeholder="Post here..."></textarea>

                        </div>
                        <!-- End Textarea -->
                    </div>

                    <div class="mb-3 grid gap-3 md:flex md:justify-between md:items-center">
                        <div class="inline-flex gap-x-3">
                            <!-- Select -->
                            <select v-model="form.type" data-hs-select='{
                          "placeholder": "Select option...",
                          "toggleTag": "<button type=\"button\" aria-expanded=\"false\"><span class=\"me-2\" data-icon></span><span class=\"text-gray-800 dark:text-neutral-200 \" data-title></span></button>",
                          "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-3 px-4 pe-9 flex items-center text-nowrap w-full cursor-pointer bg-white border border-gray-200 rounded-lg text-start text-sm focus:border-blue-500 focus:ring-blue-500 before:absolute before:inset-0 before:z-[1] dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-neutral-600",
                          "dropdownClasses": "mt-2 z-50 w-full max-h-72 p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:bg-neutral-900 dark:border-neutral-700",
                          "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:text-neutral-200 dark:focus:bg-neutral-800",
                          "optionTemplate": "<div><div class=\"flex items-center\"><div class=\"me-2\" data-icon></div><div class=\"font-semibold text-gray-800 dark:text-neutral-200 \" data-title></div></div><div class=\"mt-1.5 text-sm text-gray-500 dark:text-neutral-500 \" data-description></div></div>",
                          "extraMarkup": "<div class=\"absolute top-1/2 end-3 -translate-y-1/2\"><svg class=\"shrink-0 size-3.5 text-gray-500 dark:text-neutral-500 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m7 15 5 5 5-5\"/><path d=\"m7 9 5-5 5 5\"/></svg></div>"
                        }' class="hidden">
                                <option value="">Choose</option>
                                <option value="st" selected="" data-hs-select-option='{
                              "icon": "<svg class=\"shrink-0 size-4 text-gray-800 dark:text-neutral-200 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"lucide lucide-users\"><path d=\"M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M22 21v-2a4 4 0 0 0-3-3.87\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\"/></svg>"
                            }'>ST User</option>
                                <option value="public" data-hs-select-option='{
                              "icon": "<svg class=\"shrink-0 size-4 text-gray-800 dark:text-neutral-200 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"lucide lucide-globe-2\"><path d=\"M21.54 15H17a2 2 0 0 0-2 2v4.54\"/><path d=\"M7 3.34V5a3 3 0 0 0 3 3v0a2 2 0 0 1 2 2v0c0 1.1.9 2 2 2v0a2 2 0 0 0 2-2v0c0-1.1.9-2 2-2h3.17\"/><path d=\"M11 21.95V18a2 2 0 0 0-2-2v0a2 2 0 0 1-2-2v-1a2 2 0 0 0-2-2H2.05\"/><circle cx=\"12\" cy=\"12\" r=\"10\"/></svg>"
                            }'>Anyone</option>

                            </select>
                            <!-- End Select -->

                            <!-- Icon -->
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                class="hidden"
                                accept="image/*,video/*"
                                @change="handleFiles"
                            />
                            <button type="button" @click="triggerFileInput" title="insert media" class="hover:text-blue-600 inline-flex justify-center items-center size-[46px] rounded bg-gray-50 text-gray-800 dark:bg-neutral-700 dark:text-neutral-400">
                                <Image class="shrink-0 size-5" />
                            </button>
                            <!-- End Icon -->

                        </div>

                        <button type="submit" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                            Post
                        </button>
                    </div>
                </form>

            </div>
            <!-- End Card -->
        </div>
        <!-- End Card Section -->


    </AuthenticatedLayout>
</template>

<style scoped>

</style>
