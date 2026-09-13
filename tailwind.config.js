import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                headline: ['Bodoni Moda', 'serif'],         // Latin display / brand wordmark only
                'headline-ar': ['Amiri', 'Aref Ruqaa', 'serif'], // Arabic headings
                body: ['Be Vietnam Pro', 'sans-serif'],     // Latin UI / Numbers
                'body-ar': ['Cairo', 'Almarai', 'Tajawal', 'sans-serif'], // Arabic UI
                // Fallbacks:
                sans: ['Cairo', 'Almarai', 'Tajawal', 'Be Vietnam Pro', 'sans-serif'],
                serif: ['Amiri', 'Aref Ruqaa', 'Bodoni Moda', 'serif'],
            },
            colors: {
                primary: {
                    DEFAULT: '#4A2C4A',
                    50: '#F5EFF5',
                    100: '#EBDCEB',
                    200: '#D6BBD6',
                    300: '#C09AC0',
                    400: '#AA78AA',
                    500: '#4A2C4A',
                    600: '#422742',
                    700: '#392139',
                    800: '#311C31',
                    900: '#281728',
                    950: '#201220',
                },
                secondary: {
                    DEFAULT: '#E8A2B6',
                    50: '#FDF6F8',
                    100: '#FAECF0',
                    200: '#F5D9E1',
                    300: '#F0C6D3',
                    400: '#ECB2C4',
                    500: '#E8A2B6',
                    600: '#D192A4',
                    700: '#B88090',
                    800: '#9E6E7C',
                    900: '#825A66',
                },
                tertiary: {
                    DEFAULT: '#FFF9F0',
                    50: '#FFFFFF',
                    100: '#FFFCFA',
                    200: '#FFF9F0',
                    300: '#FFF4E0',
                    400: '#FFEED1',
                    500: '#FFE9C2',
                    600: '#E6D1AE',
                    700: '#CDBA9C',
                    800: '#B4A389',
                    900: '#9A8C75',
                },
                neutral: {
                    DEFAULT: '#7B7678',
                    50: '#F5F5F5',
                    100: '#EBEAEA',
                    200: '#D6D4D5',
                    300: '#C0BEBF',
                    400: '#ABA8A9',
                    500: '#7B7678',
                    600: '#6E6A6B',
                    700: '#625E60',
                    800: '#565254',
                    900: '#4A4748',
                },
                background: '#d6bbd6', // Darker soft plum/lavender background
                surface: '#FFFFFF',
                success: '#16A34A', // TODO: confirm exact hex
                warning: '#F59E0B', // TODO: confirm exact hex
                error: '#EF4444',
            },
            borderRadius: {
                'card': '1.5rem', // rounded-2xl/3xl per tokens
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                'soft': '0 4px 20px rgba(0, 0, 0, 0.08)',
            },
        },
    },

    plugins: [forms],
};
