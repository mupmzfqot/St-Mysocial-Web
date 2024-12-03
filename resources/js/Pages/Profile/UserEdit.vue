<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import HomeLayout from "@/Layouts/HomeLayout.vue";
import ImageCropper from "@/Components/ImageCropper.vue";
import {ref} from "vue";
import {MessageSquareMore} from "lucide-vue-next";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    media: Object
});

const { user: user } = usePage().props.auth;

</script>

<template>
    <Head title="User Profile" />

    <HomeLayout>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Background -->
            <div class="h-40 bg-cover bg-center" :style="{ backgroundImage: `url(${user.cover_image})` }">
            </div>
            <!-- Profile Section -->
            <div class="p-4 text-center">
                <div class="relative w-24 h-24 mx-auto -mt-16 bg-white rounded-full border-4 border-white overflow-hidden">
                    <img :src="user.avatar" alt="Profile"
                         class="w-full h-full object-cover">
                </div>
                <h3 class="mt-2 font-semibold text-gray-800">{{ user.name }}</h3>
                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                <!-- Stats -->
                <div class="flex justify-center items-center space-x-6 mt-4 text-gray-800 text-sm">
                    <div class="text-center">
                        <p class=""><b>20</b> Stars</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>10</b> Followers</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>15</b> Projects</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <ImageCropper type="avatar" :url="route('profile.upload-image')" :default-image="media.avatar" />
                </div>

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
