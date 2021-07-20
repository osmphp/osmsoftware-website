module.exports = {
    purge: {
        content: [
            'temp/*/*/views/**/*.php',
        ]
    },
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {}
    },
    variants: {},
    plugins: [
        require('@tailwindcss/typography')
    ],
}