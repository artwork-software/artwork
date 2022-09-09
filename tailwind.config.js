const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    mode: 'jit',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        "./node_modules/flowbite/**/*.js"
    ],

    theme: {
        extend: {
            colors: {
                primary:'#27233C',
                primaryHover:'#372F60',
                success:'#1FC687',
                error:'#FD6D73',
                secondary:'#A7A6B1',
                secondaryHover:'#FCFCFB',
                help: '#93929D',
                backgroundGray: '#F5F5F3',
                buttonBlue:'#3017AD',
                buttonHover:'#2D1FDE'


            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                nanum: "Nanum Pen Script",
                lexend: "Lexend"
            },
            boxShadow: {
                'buttonBlue': '0 35px 60px -15px #2D1FDE'
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('flowbite/plugin')
    ],
};
