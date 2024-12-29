<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import HomeLayout from "@/Layouts/HomeLayout.vue";
import ImageCropper from "@/Components/ImageCropper.vue";
import {ref} from "vue";
import {MessageSquareMore} from "lucide-vue-next";
import { Dialog, DialogPanel, DialogTitle, TransitionRoot } from '@headlessui/vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    media: Object,
    totalPosts: 0,
    totalLikes: 0,
    totalComments: 0,
});

const { user: user } = usePage().props.auth;
const showAvatarCropper = ref(false);
const showCoverCropper = ref(false);

</script>

<template>
    <Head title="User Profile" />

    <HomeLayout>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Cover Image Section -->
            <div class="relative">
                <div class="h-40 bg-cover bg-center" :style="{ backgroundImage: `url(${user.cover_image})` }">
                    <!-- Cover Image Edit Button -->
                    <button
                        @click="showCoverCropper = true"
                        class="absolute top-2 right-2 p-2 bg-white/80 hover:bg-white rounded-full shadow-md transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>

                <!-- Avatar Section -->
                <div class="relative w-24 h-24 mx-auto -mt-16 bg-white rounded-full border-4 border-white overflow-hidden group">
                    <img :src="user.avatar" alt="Profile" class="w-full h-full object-cover">
                    <!-- Avatar Edit Button -->
                    <button
                        @click="showAvatarCropper = true"
                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="p-4 text-center">
                <h3 class="mt-2 font-semibold text-gray-800">{{ user.name }}</h3>
                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                <!-- Stats -->
                <div class="flex justify-center items-center space-x-6 mt-4 text-gray-800 text-sm">
                    <div class="text-center">
                        <p class=""><b>{{ totalPosts }}</b> Posts</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>{{ totalLikes }}</b> Likes</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>{{ totalComments }}</b> Comments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Croppers Modals -->
        <TransitionRoot appear :show="showAvatarCropper" as="template">
            <Dialog as="div" @close="showAvatarCropper = false" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 shadow-xl transition-all">
                        <DialogTitle class="text-lg font-medium mb-4">Edit Profile Picture</DialogTitle>
                        <ImageCropper
                            type="avatar"
                            :url="route('profile.upload-image')"
                            :default-image="media.avatar"
                            @uploaded="showAvatarCropper = false"
                        />
                    </DialogPanel>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot appear :show="showCoverCropper" as="template">
            <Dialog as="div" @close="showCoverCropper = false" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto">
                    <DialogPanel class="w-full max-w-4xl mx-auto transform rounded-lg bg-white p-6 shadow-xl transition-all">
                        <div class="flex justify-between items-center mb-4">
                            <DialogTitle class="text-lg font-medium">Edit Cover Photo</DialogTitle>
                            <button
                                @click="showCoverCropper = false"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
                            <ImageCropper
                                type="cover_image"
                                :url="route('profile.upload-image')"
                                :default-image="media.cover"
                                :aspect-ratio="3/1"
                                :rectangular="true"
                                @uploaded="showCoverCropper = false"
                            />
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
        </TransitionRoot>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-2">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </HomeLayout>
</template>

<style scoped>
</style>
