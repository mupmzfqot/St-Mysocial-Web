<script setup>
import { onMounted, ref, computed } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const props = defineProps({
    type: {
        type: String,
        default: 'text'
    },
    autocomplete: {
        type: String,
        default: null
    },
    placeholder: {
        type: String,
        default: ''
    },
    inputClass: {
        type: String,
        default: ''
    },
    class: {
        type: String,
        default: ''
    }
});

const input = ref(null);

const inputClasses = computed(() => {
    const customClass = props.inputClass || props.class || '';
    return [
        customClass,
        'py-2 px-3 pe-11 block w-full border-gray-200 shadow-sm rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600'
    ].filter(Boolean).join(' ');
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :type="type"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        :class="inputClasses"
        v-model="model"
        ref="input"
    />
</template>
