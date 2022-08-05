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
                eventType0:'#A7A6B1',
                eventType1:'#641A54',
                eventType2:'#DA3F87',
                eventType3:'#EB7A3D',
                eventType4:'#F1B640',
                eventType5:'#86C554',
                eventType6:'#2EAA63',
                eventType7:'#3DC3CB',
                eventType8:'#168FC3',
                eventType9:'#4D908E',
                eventType10:'#21485C'

            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                nanum: "Nanum Pen Script",
                lexend: "Lexend"
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('flowbite/plugin')
    ],
};
