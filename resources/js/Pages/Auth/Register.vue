<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import StrongPassword from "@/Components/StrongPassword.vue";
import TogglePassword from "@/Components/TogglePassword.vue";
import { ref } from 'vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const error_message = ref('');

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: async (errors) => {
            error_message.value = Object.values(errors)[0];
            console.log(Object.values(errors));
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registration" />

        <div class="w-[470px] container sm:mx-4 lg:mx-auto mt-7 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-700">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Registration</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                        Already have an account?
                        <Link :href="route('login')" class="text-blue-600 decoration-2 hover:underline focus:outline-none focus:underline font-medium dark:text-blue-500" href="../examples/html/signin.html">
                            Login here
                        </Link>
                    </p>
                </div>

                <div class="mt-5">
                    <hr class="mb-5">
                    <div v-if="error_message" class="mb-5 bg-red-50 border-s-4 border-red-500 p-4 dark:bg-red-800/30" role="alert" tabindex="-1" aria-labelledby="hs-bordered-red-style-label">
                        <div class="flex">
                        <div class="shrink-0">
                            <!-- Icon -->
                            <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800 dark:border-red-900 dark:bg-red-800 dark:text-red-400">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                            </span>
                            <!-- End Icon -->
                        </div>
                        <div class="ms-3">
                            <h3 id="hs-bordered-red-style-label" class="text-gray-800 font-semibold dark:text-white">
                            Error!
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-neutral-400">
                            {{ error_message }}
                            </p>
                        </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit">
                        <div class="grid gap-y-4">
                            <!-- Form Group -->
                            <div>
                                <label for="name" class="block text-sm mb-2 dark:text-white">Full Name</label>
                                <div class="relative">
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                        placeholder="Enter your name"
                                    />
                                </div>
                                <p class="text-sm text-red-600 mt-2" v-if="form.errors.name">{{ form.errors.name }}</p>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div>
                                <label for="email" class="block text-sm mb-2 dark:text-white">Email address</label>
                                <div class="relative">
                                    <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="form.email"
                                        required
                                        autocomplete="email"
                                        placeholder="Email"
                                    />
                                </div>
                                <p class="text-sm text-red-600 mt-2" v-if="form.errors.email">{{ form.errors.email }}</p>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div>
                                <label for="username" class="block text-sm mb-2 dark:text-white">Username</label>
                                <div class="relative">
                                    <TextInput
                                        id="username"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.username"
                                        required
                                        placeholder="Username"
                                    />
                                </div>
                                <p class="text-sm text-red-600 mt-2" v-if="form.errors.username">{{ form.errors.username }}</p>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <!-- <div>
                                <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
                                <div class="relative">
                                    <StrongPassword required v-model="form.password" />
                                </div>
                                <p class="text-sm text-red-600 mt-2" v-if="form.errors.password">{{ form.errors.password }}</p>
                            </div> -->
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <!-- <div>
                                <label for="confirm-password" class="block text-sm mb-2 dark:text-white">Confirm Password</label>
                                <TogglePassword v-model="form.password_confirmation" placeholder="Confirm Password" />
                            </div> -->
                            <!-- End Form Group -->

                            <button type="submit" class="w-full py-3 mt-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent btn-primary text-white hover:bg-gray-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                Register
                            </button>
                        </div>
                    </form>
                    <!-- End Form -->
                </div>
            </div>
        </div>

    </GuestLayout>
</template>
<style scoped>
.btn-primary {
    background: rgb(68,30,167)
}
</style>
