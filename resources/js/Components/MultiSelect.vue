<script setup>
import {computed} from "vue";

const props = defineProps({
    stUsers: Object,
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
    get: () => props.modelValue || [],
    set: (value) => emit('update:modelValue', value)
});

console.log("model_value: "+props.modelValue)
</script>

<template>
    <div class="py-2">
        <!-- Select -->
        <label class="hidden" for="hs-tags-input">Tags label</label>
        <select v-model="model" multiple data-hs-select='{
              "placeholder": "Tag people...",
              "dropdownClasses": "mt-2 z-50 w-full max-h-72 p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:bg-neutral-900 dark:border-neutral-700",
              "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:text-neutral-200 dark:focus:bg-gray-100",
              "mode": "tags",
              "wrapperClasses": "relative ps-0.5 pe-9 min-h-[46px] flex items-center flex-wrap text-nowrap w-full border border-gray-200 rounded-lg text-start text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400",
              "tagsItemTemplate": "<div class=\"flex flex-nowrap items-center relative z-10 bg-white border border-gray-200 rounded-full p-1 m-1 dark:bg-neutral-900 dark:border-neutral-700 \"><div class=\"size-6 me-1\" data-icon></div><div class=\"whitespace-nowrap text-gray-800 dark:text-neutral-200 \" data-title></div><div class=\"inline-flex shrink-0 justify-center items-center size-5 ms-2 rounded-full text-gray-800 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm dark:bg-neutral-700/50 dark:hover:bg-neutral-700 dark:text-neutral-400 cursor-pointer\" data-remove><svg class=\"shrink-0 size-3\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M18 6 6 18\"/><path d=\"m6 6 12 12\"/></svg></div></div>",
              "tagsInputId": "hs-tags-input",
              "tagsInputClasses": "rounded-lg order-1 border-none text-sm dark:bg-neutral-900 dark:placeholder-neutral-500 dark:text-neutral-400",
              "optionTemplate": "<div class=\"flex items-center\"><div class=\"size-8 me-2\" data-icon></div><div><div class=\"text-sm text-gray-800 dark:text-neutral-200 \" data-title></div><div class=\"text-xs text-gray-500 dark:text-neutral-500 \" data-description></div></div><div class=\"ms-auto\"><span class=\"hidden hs-selected:block\"><svg class=\"shrink-0 size-4 text-blue-600\" xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" viewBox=\"0 0 16 16\"><path d=\"M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z\"/></svg></span></div></div>",
              "extraMarkup": "<div class=\"absolute top-1/2 end-3 -translate-y-1/2\"></div>"
            }' class="hidden">
            <option v-for="user in stUsers" :key="user.id" :value="user.id" :selected="model.includes(user.id)">{{ user.name }}</option>
        </select>
        <!-- End Select -->
    </div>
</template>

<style scoped>
.preline-multiselect .preline-input[type="search"] {
    border: none !important;
    box-shadow: none !important;
}
</style>
