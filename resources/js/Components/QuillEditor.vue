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

// URL patterns that should not be capitalized
const urlPatterns = [
    /^https?:\/\//i,           // http:// or https://
    /^www\./i,                  // www.
    /^ftp:\/\//i,               // ftp://
    /^mailto:/i,                // mailto:
    /^tel:/i,                   // tel:
    /^#/,                       // hashtag
    /^@/,                       // mention
    /^\d+/,                     // starts with number
    /^[a-z]+\.[a-z]+/i,        // domain-like (e.g., example.com)
];

// Technical terms that shouldn't be capitalized
const technicalTerms = [
    'api', 'url', 'http', 'https', 'www', 'ftp', 'mailto', 'tel',
    'css', 'html', 'js', 'json', 'xml', 'sql', 'php', 'py', 'java',
    'git', 'npm', 'yarn', 'composer', 'docker', 'kubernetes',
    'aws', 'gcp', 'azure', 'heroku', 'vercel', 'netlify',
    'react', 'vue', 'angular', 'node', 'express', 'laravel',
    'mysql', 'postgresql', 'mongodb', 'redis', 'elasticsearch'
];

const capitalizeFirstLetter = () => {
    const range = quill.getSelection();
    if(range && range.index === 0) {
        let text = quill.getText();
        
        // Skip capitalization if text is empty or too short
        if (!text || text.length < 2) {
            return;
        }
        
        // Check if text starts with any URL pattern
        const isUrl = urlPatterns.some(pattern => pattern.test(text));
        
        // Check if text starts with technical term
        const isTechnicalTerm = technicalTerms.some(term => 
            text.toLowerCase().startsWith(term.toLowerCase())
        );
        
        // Only capitalize if it's not a URL and not a technical term
        if (!isUrl && !isTechnicalTerm) {
            let firstLetter = text.charAt(0).toUpperCase();
            
            if(text.charAt(0) !== firstLetter) {
                quill.deleteText(0, 1);
                quill.insertText(0, firstLetter);
            }
        }
    }
}

// Handle paste events specifically
const handlePaste = () => {
    // Delay the check to ensure paste content is fully processed
    // Increased timeout for longer URLs
    setTimeout(() => {
        try {
            const text = quill.getText();
            if (text && text.length > 0) {
                // Check if pasted content starts with URL patterns
                const isUrl = urlPatterns.some(pattern => pattern.test(text));
                
                if (isUrl) {
                    // If it's a URL, ensure ALL characters stay lowercase
                    const correctedText = text.toLowerCase();
                    
                    // Only replace if the text actually changed
                    if (text !== correctedText) {
                        // Use safer text replacement method
                        quill.deleteText(0, text.length);
                        quill.insertText(0, correctedText);
                    }
                }
            }
        } catch (error) {
            console.warn('Paste handling error:', error);
        }
    }, 50); // Increased from 10ms to 50ms for longer URLs
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

    // Listen for text changes
    quill.on('text-change', () => {
        capitalizeFirstLetter();
        emit('update:value', quill.root.innerHTML);
    });
    
    // Listen for paste events specifically - REMOVED duplicate listener
    quill.on('paste', handlePaste);
    
    // REMOVED: quill.root.addEventListener('paste', handlePaste);
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