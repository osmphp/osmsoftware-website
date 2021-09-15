const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    purge: {
        content: [
            'temp/*/*/views/**/*.php',
        ]
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