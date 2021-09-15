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
                    /yellow-600$/,
                    /green-700$/,
                    /blue-400$/,
                    /green-500$/,
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