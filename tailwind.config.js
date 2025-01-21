// import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        'node_modules/preline/dist/*.js',
    ],

    theme: {
        extend: {
            animation: {
                'bounce': 'bounce 1s infinite',
                'fade-in': 'fadeIn 0.5s ease-out',
            },
            keyframes: {
                bounce: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-25%)' },
                },
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                }
            }
        }
    },

    plugins: [
        forms,
        require('preline/plugin'),
    ],

    darkMode: true,
};
