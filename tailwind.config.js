module.exports = {
    content: [
        'resources/views/*.antlers.html',
        'resources/views/*.blade.php',
        'resources/css/*.css',
        'resources/js/components/*.vue',
    ],
    theme: {
    },
    plugins: [
        require('@tailwindcss/typography'),
    ]
}
