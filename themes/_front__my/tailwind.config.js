const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    purge: {
        content: [
            'temp/*/*/views/**/*.php',
        ],
        options: {
            safelist: {
                standard: [
                    /gray-700$/,
                    /black$/,
                    /yellow-600$/,
                    /yellow-700$/,
                    /green-700$/,
                    /green-800$/,
                    /blue-400$/,
                    /blue-500$/,
                    /green-500$/,
                    /green-600$/,
                ],
            }
        }
    },
    darkMode: false, // or 'media' or 'class'
    theme: {
        fontFamily: {
          'sans': ['"Titillium Web"', ...defaultTheme.fontFamily.sans],
          'serif': [...defaultTheme.fontFamily.serif],
          'mono': ['"Syne Mono"', ...defaultTheme.fontFamily.mono]
        },
        screens: {
            xs: '480px', // custom breakpoint
            sm: '640px',
            md: '768px',
            lg: '1024px',
            xl: '1280px',
            '2xl': '1536px',
        },
    },
    variants: {},
    plugins: [
        require('@tailwindcss/typography')
    ],
}