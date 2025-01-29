<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm, usePage} from '@inertiajs/vue3';
import TogglePassword from "@/Components/TogglePassword.vue";
import {useRecaptchaProvider, Checkbox as RecaptchaCheckbox} from "vue-recaptcha";
import {ref, computed, onMounted, watch} from 'vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    recaptcha: '',
});

const loginError = ref({
    message: '',
    remainAttempts: null,
    maxAttempts: null,
    unlockAt: null
});

const createRecaptchaProviderSingleton = () => {
    let instance = null;
    return {
        initialize() {
            if (!instance) {
                instance = useRecaptchaProvider();
            }
            return instance;
        },
        reset() {
            instance = null;
        }
    };
};

const recaptchaProviderManager = createRecaptchaProviderSingleton();

const recaptchaKey = ref(0);
const recaptchaProviderInitialized = ref(false);

if (!recaptchaProviderInitialized.value) {
    useRecaptchaProvider();
    recaptchaProviderInitialized.value = true;
}

const resetRecaptcha = () => {
    recaptchaKey.value++;
    form.reset();
    loginError.value = {
        message: '',
        remainAttempts: null,
        maxAttempts: null,
        unlockAt: null
    };
};

onMounted(() => {
    resetRecaptcha();
});

watch(() => usePage().component.value, () => {
    resetRecaptcha();
});

const submit = () => {
    loginError.value = {
        message: '',
        remainAttempts: null,
        maxAttempts: null,
        unlockAt: null
    };

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            recaptchaKey.value++;
            nextTick();

            if (errors.email && Array.isArray(errors.email)) {
                const errorDetails = errors.email[0];
                loginError.value = {
                    message: errorDetails.message || 'Login failed',
                    remainAttempts: errorDetails.remain_attempts ?? null,
                    maxAttempts: errorDetails.max_attempts ?? null,
                    unlockAt: errorDetails.unlock_at ? new Date(errorDetails.unlock_at) : null
                };
            }
        }
    });
};

const formattedUnlockTime = computed(() => {
    if (loginError.value.unlockAt) {
        return loginError.value.unlockAt.toLocaleString();
    }
    return null;
});
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <!-- Rate Limit Error Message -->
        <div v-if="loginError.message" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p>{{ loginError.message }}</p>

            <!-- Remaining Attempts Message -->
            <p v-if="loginError.remainAttempts !== null && loginError.remainAttempts >= 0">
                Remaining attempts: {{ loginError.remainAttempts }} / {{ loginError.maxAttempts }}
            </p>

            <!-- Unlock Time Message -->
            <p v-if="formattedUnlockTime">
                You can try again after: {{ formattedUnlockTime }}
            </p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm sm:flex dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="shrink-0 relative w-full rounded-t-xl overflow-hidden pt-[40%] sm:rounded-s-xl sm:max-w-60 md:rounded-se-none md:max-w-xs">
                <img class="size-full absolute top-0 start-0 object-cover" src="../../../images/background.png" alt="Card Image">
            </div>
            <div class="flex flex-wrap">
                <div class="p-4 flex flex-col h-full sm:p-7">
                    <h3 class="text-2xl text-center font-bold text-gray-800 dark:text-white">
                        Login
                    </h3>
                    <div :class="[form.errors.email ? 'mt-0': 'mt-4']">
                        <div class="py-3" v-if="form.errors.email">
                            <div class="bg-yellow-50 border border-red-400 text-sm text-red-800 rounded-lg p-4 dark:bg-yellow-800/10 dark:border-yellow-900 dark:text-yellow-500" role="alert" tabindex="-1" aria-labelledby="hs-with-description-label">
                                <div class="flex">
                                    <div class="shrink-0">
                                        <svg class="shrink-0 size-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                            <path d="M12 9v4"></path>
                                            <path d="M12 17h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="ms-4">
                                        <h3 id="hs-with-description-label" class="text-sm font-semibold">
                                            Login Failed!
                                        </h3>
                                        <div class="mt-1 text-sm text-gray-800">
                                            {{ form.errors.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="password" value="Password" />
                                <TogglePassword v-model="form.password" />
                            </div>

                            <div class="block mt-4">
                                <RecaptchaCheckbox :key="recaptchaKey" v-model="form.recaptcha" theme="light" size="normal" />
                                <InputError class="mt-2" :message="form.errors.recaptcha" />
                            </div>

                            <div class="block mt-4">
                                <label class="flex items-center">
                                    <Checkbox name="remember" v-model:checked="form.remember" />
                                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                                </label>
                            </div>

                            <div class="flex items-center justify-between mt-4">
                                <PrimaryButton class="" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Log in
                                </PrimaryButton>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                >
                                    Forgot your password?
                                </Link>
                            </div>
                        </form>
                    </div>
                    <p class="mt-1 text-white">
                        Some quick example text to build on the card title and make up the bulk of the card's content.
                    </p>

                </div>
            </div>
        </div>
    </GuestLayout>
</template>
