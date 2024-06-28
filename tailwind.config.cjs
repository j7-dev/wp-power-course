/** @type {import('tailwindcss').Config} */
// eslint-disable-next-line no-undef
module.exports = {
    important: true,
    corePlugins: {
        preflight: false,
    },
    content: ['./js/src/**/*.{js,ts,jsx,tsx}', './inc/**/*.php'],
    theme: {
        extend: {
            screens: {
                sm: '576px', // iphone SE
                md: '810px', // ipad Portrait
                lg: '1080px', // ipad Landscape
                xl: '1280px', // mac air
                xxl: '1440px',
            },
        },
    },
    plugins: [
        require('daisyui'),
    ],
    safelist: [],
    daisyui: {
        themes: [
            {
                power: {
                    "color-scheme": "light",
                    "primary": "#377cfb",
                    "primary-content": "#223D30",
                    "secondary": "#66cc8a",
                    "secondary-content": "#fff",
                    "accent": "#f68067",
                    "accent-content": "#000",
                    "neutral": "#333c4d",
                    "neutral-content": "#f9fafb",
                    "base-100": "oklch(100% 0 0)",
                    "base-content": "#333c4d",
                    "--animation-btn": "0",
                    "--animation-input": "0",
                    "--btn-focus-scale": "1",
                },
            }
        ],
        prefix: 'pc-', // prefix for daisyUI classnames (components, modifiers and responsive class names. Not colors)
    },
}
