import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: "#7C3AED", // Violet-600
                "primary-light": "#8B5CF6", // Violet-500
                "background-light": "#F3F4F6", // Gray-100
                "background-dark": "#111827", // Gray-900
                "card-light": "#FFFFFF",
                "card-dark": "#1F2937",
                "text-main-light": "#1F2937",
                "text-main-dark": "#F9FAFB",
                "text-sub-light": "#6B7280",
                "text-sub-dark": "#9CA3AF",
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter', 'sans-serif'],
                body: ['Inter', 'sans-serif'],
            },
            borderRadius: {
                DEFAULT: "0.5rem",
                'xl': '1rem',
                '2xl': '1.5rem',
            },
            boxShadow: {
                'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                'glow': '0 4px 20px -2px rgba(124, 58, 237, 0.3)',
            },
        },
    },

    plugins: [forms],
};
