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
        }
    },
    variants: {},
    plugins: [
        require('@tailwindcss/typography')
    ],
}