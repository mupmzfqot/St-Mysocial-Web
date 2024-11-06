<script setup>
import {Head, Link, useForm} from "@inertiajs/vue3";
import {CheckCircle, Heart, Image, MessageSquareText, MinusCircle,} from "lucide-vue-next";
import {ref} from "vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";

const fileInput = ref(null);
const previews = ref([]);

const props = defineProps({
    posts: Object
});

const form = useForm({
    content: '',
    files: [],
    group: 'st_user',
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
    form.post(route('user-post.store'), {
        onSuccess: () => {
            console.log('Upload successful');
            form.reset();
            previews.value = [];
        },
        onError: (errors) => {
            console.error('Upload failed:', errors);
        },
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Create New Post" />
    <HomeLayout>
        <!-- Card Section -->
        <div class="w-full">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow p-4 dark:bg-neutral-800">
                <label class="font-semibold text-gray-800 dark:text-neutral-200 mb-2">New Post</label>
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
                            <select v-model="form.group" data-hs-select='{
                          "placeholder": "Select option...",
                          "toggleTag": "<button type=\"button\" aria-expanded=\"false\"><span class=\"me-2\" data-icon></span><span class=\"text-gray-800 dark:text-neutral-200 \" data-title></span></button>",
                          "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-3 px-4 pe-9 flex items-center text-nowrap w-full cursor-pointer bg-white border border-gray-200 rounded-lg text-start text-sm focus:border-blue-500 focus:ring-blue-500 before:absolute before:inset-0 before:z-[1] dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-neutral-600",
                          "dropdownClasses": "mt-2 z-50 w-full max-h-72 p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:bg-neutral-900 dark:border-neutral-700",
                          "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:text-neutral-200 dark:focus:bg-neutral-800",
                          "optionTemplate": "<div><div class=\"flex items-center\"><div class=\"me-2\" data-icon></div><div class=\"font-semibold text-gray-800 dark:text-neutral-200 \" data-title></div></div><div class=\"mt-1.5 text-sm text-gray-500 dark:text-neutral-500 \" data-description></div></div>",
                          "extraMarkup": "<div class=\"absolute top-1/2 end-3 -translate-y-1/2\"><svg class=\"shrink-0 size-3.5 text-gray-500 dark:text-neutral-500 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m7 15 5 5 5-5\"/><path d=\"m7 9 5-5 5 5\"/></svg></div>"
                        }' class="hidden">
                                <option value="">Choose</option>
                                <option value="st_user" selected="" data-hs-select-option='{
                              "icon": "<svg class=\"shrink-0 size-4 text-gray-800 dark:text-neutral-200 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"lucide lucide-users\"><path d=\"M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M22 21v-2a4 4 0 0 0-3-3.87\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\"/></svg>"
                            }'>ST User</option>
                                <option value="all" data-hs-select-option='{
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
                            Create Post
                        </button>
                    </div>
                </form>

            </div>
            <!-- End Card -->
        </div>
        <!-- End Card Section -->

        <!-- My Posts -->
        <div>
            <p class="relative py-4 mx-2 text-lg font-bold text-gray-800 dark:text-white focus:outline-none">
                My Recent Posts
            </p>
        </div>
        <div v-for="post in posts.data" class="flex flex-col bg-white border shadow-sm rounded-xl p-4 md:p-5 mb-3 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex items-center">
                <div class="shrink-0">
                    <img class="size-10 rounded-full" :src="post.author.avatar" alt="Avatar">
                </div>
                <div class="ms-4">
                    <div class="text-base font-semibold text-gray-800 dark:text-neutral-400">{{ post.author.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-neutral-500">{{ post.created_at }}</div>

                </div>
                <span v-if="!post.published" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                  <MinusCircle class="size-3" />Not Published
                </span>
                <span v-if="post.published" class="py-1 px-3 inline-flex items-center gap-x-1 ms-auto text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                  <CheckCircle class="size-3" />Published
                </span>
            </div>
            <div class="mt-2 text-gray-500 dark:text-neutral-400" v-html="post.post"></div>

            <!-- Image Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-3/4 py-2" v-if="post.media.length > 0">
                <a class="group block relative overflow-hidden rounded-lg" href="#" v-for="media in post.media">
                    <div v-if="media.mime_type !== 'video/mp4'">
                        <img class="w-full size-40 object-cover bg-gray-100 rounded-lg dark:bg-neutral-800" :src="media.preview_url" alt="Project">
                        <div class="absolute bottom-1 end-1 opacity-0 group-hover:opacity-100 transition">
                            <div class="flex items-center gap-x-1 py-1 px-2 bg-white border border-gray-200 text-gray-800 rounded-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                                <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <span class="text-xs">View</span>
                            </div>
                        </div>
                    </div>

                    <video v-else-if="media.mime_type.startsWith('video/')" controls class="aspect-video rounded-lg">
                        <source :src="media.original_url" :type="media.mime_type" />
                        Your browser does not support the video tag.
                    </video>
                </a>

            </div>
            <!-- End Image Grid -->

            <div class="inline-flex gap-x-3" v-if="post.published">
                <Link :href="route('user-post.show-post', post.id)" class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <MessageSquareText class="shrink-0 size-4 text-blue-600" />
                    {{ post.comment_count }} Comments

                </Link>
                <div class="mt-3 inline-flex items-center gap-x-1 text-sm rounded-lg border border-transparent text-neutral-600 decoration-2 hover:text-blue-700 focus:outline-none focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                    <a href="" @click.prevent="sendLike(post.id)">
                        <Heart class="shrink-0 size-4 fill-red-500 text-transparent" v-if="post.is_liked" />
                        <Heart class="shrink-0 size-4 text-gray-500" v-else />
                    </a>
                    {{ post.like_count }} Likes
                </div>
            </div>
        </div>

        <!-- end my posts -->


    </HomeLayout>
</template>

<style scoped>

</style>
