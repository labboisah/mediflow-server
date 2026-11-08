import forms from '@tailwindcss/forms';

export default {
    content: [
        './app/**/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './routes/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                med: {
                    ink: '#17211d',
                    muted: '#61706a',
                    line: '#d9e4df',
                    canvas: '#f5f8f6',
                    surface: '#ffffff',
                    primary: '#147a55',
                    primaryDark: '#0f5f43',
                    accent: '#f59f3d',
                    info: '#2f6fbb',
                    danger: '#c2410c',
                },
            },
            boxShadow: {
                panel: '0 1px 2px rgba(23, 33, 29, 0.05), 0 12px 32px rgba(23, 33, 29, 0.07)',
            },
            fontFamily: {
                sans: ['Figtree', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [forms],
};
