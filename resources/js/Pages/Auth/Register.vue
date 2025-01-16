<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StrongPassword from "@/Components/StrongPassword.vue";
import TogglePassword from "@/Components/TogglePassword.vue";
// import {useRecaptchaProvider, Checkbox as RecaptchaCheckbox} from "vue-recaptcha";

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    recaptcha: ''
});

// useRecaptchaProvider();

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registration" />

        <div class="lg:w-[calc(27%-2rem)] md:w-[calc(40%-2rem)] container sm:mx-4 lg:mx-auto mt-7 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-700">
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
                            <div>
                                <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
                                <div class="relative">
                                    <StrongPassword required v-model="form.password" />
                                </div>
                                <p class="text-sm text-red-600 mt-2" v-if="form.errors.password">{{ form.errors.password }}</p>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div>
                                <label for="confirm-password" class="block text-sm mb-2 dark:text-white">Confirm Password</label>
                                <TogglePassword v-model="form.password_confirmation" placeholder="Confirm Password" />
                            </div>
                            <!-- End Form Group -->

<!--                            <div>-->
<!--                                <RecaptchaCheckbox v-model="form.recaptcha" theme="light" size="normal" />-->
<!--                            </div>-->
<!--                            <div v-if="form.errors.recaptcha">-->
<!--                                <p class="text-sm text-red-600 mt-2">{{ form.errors.recaptcha }}</p>-->
<!--                            </div>-->

                            <button type="submit" class="w-full py-3 mt-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
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
