const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        'temp/*/*/views/**/*.php',
    ],
    safelist: [
        { pattern: /gray-700$/ },
        { pattern: /black$/ },
        { pattern: /yellow-600$/ },
        { pattern: /yellow-700$/ },
        { pattern: /green-700$/ },
        { pattern: /green-800$/ },
        { pattern: /blue-400$/ },
        { pattern: /blue-500$/ },
        { pattern: /green-500$/ },
        { pattern: /green-600$/ },
        { pattern: /red-800$/ },
        { pattern: /red-900$/ },
    ],
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