<script setup>
import { onBeforeMount, onMounted, ref } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const props = defineProps({
    value: {
        type: String,
        default: '',
    }
})

const emit = defineEmits(['update:value']);
const quillEditor = ref(null);

let quill = null;

const capitalizeFirstLetter = () => {
    const range = quill.getSelection();
    if(range && range.index === 0) {
        let text = quill.getText();
        let firstLetter = text.charAt(0).toUpperCase();

        if(text.charAt(0) !== firstLetter) {
            quill.deleteText(0, 1);
            quill.insertText(0, firstLetter);
        }
    }
}

onMounted(() => {
    quill = new Quill(quillEditor.value, {
        theme: 'snow',
        placeholder: "what's in your mind?",
        modules: {
            toolbar: false
        },
        options: {
            normalizeWhitespace: true
        }
    });

    if(props.value) {
        quill.root.innerHTML = props.value;
    }

    quill.on('text-change', () => {
        capitalizeFirstLetter();
        emit('update:value', quill.root.innerHTML);
    });
});

const getContent = () => {
    return quill.root.innerHTML;
};

onBeforeMount(() => {
    if(quill) {
        quill.off('text-change');
        quill = null;
    }
});

defineExpose({
    getContent,
    getSelection: () => quill.getSelection(),
    setContent: (value) => {
        quill.clipboard.dangerouslyPasteHTML(value);
    },
    getLength: () => quill.getLength(),
    insertText: (index, text) => quill.insertText(index, text),
    deleteText: (index, length) => quill.deleteText(index, length),
    format: (formatName, formatValue) => {
        quill.format(formatName, formatValue);
    },
    formatText: (index, length, formatName, formatValue, text) => {
        quill.formatText(index, length, formatName, formatValue, { text });
    }
});

</script>

<template>
    <div ref="quillEditor" class="quill-editor"></div>
</template>

<style scoped>
.quill-editor {
    overflow: auto;
    border-radius: 0.5rem;
}
</style>