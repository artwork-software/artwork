const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require("tailwindcss/colors")

module.exports = {
    mode: 'jit',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
        "./node_modules/vue-tailwind-datepicker/**/*.js"
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
                darkInputBg:'rgba(252,252,251,0.15)',
                darkInputText: '#D8D7DE',
                help: '#93929D',
                darkGray: '#616069',
                darkGrayBg: 'rgba(255,255,255,0.15)',
                backgroundGray: '#F5F5F3',
                buttonBlue:'#3017AD',
                buttonHover:'#2D1FDE',
                tagBg: 'rgba(48,23,173,0.1)',
                tagBgGray: '#5E5C66',
                tag: 'rgba(48,23,173,0.3)',
                tagText: '#3017AD',
                backgroundBlue: '#3017AD34',
                lightBackgroundGray: '#F4F4F3',
                checkBoxBg: '#474459',
                silverGray:'#CECDD8',
                userBg: '#EDEDEC',
                colorOfAction: '#E8E4f5',
                menuButtonBlue: '#3017AD',
                shiftText: '#82818A',
                linkOnDarkColor: '#BDB6F0',
                "vtd-primary": colors.sky,
                "vtd-secondary": colors.gray,
                // Artwork colors
                artwork: {
                    messages: {
                        success: '#25cd0e',
                        waring: '#ecce00',
                        error: '#ef4444',
                        info: '#a7a6b1'
                    },
                    buttons: {
                        create: '#3073ae',
                        context: '#6f6f6f',
                        hover: '#1c77d7',
                        default: '#2a3d75',
                        update: '#2a3d75',
                    },
                    context: {
                        light: '#e8e8e8',
                        dark: '#6f6f6f'
                    },
                    project: {
                        background: '#eee',
                    },
                    calendar: {

                    },
                    icons: {
                        default: {
                            background: 'rgba(48,23,173,0.1)',
                            color: '#3017AD'
                        },
                        darkGreen: {
                            background: 'rgba(56,173,23,0.1)',
                            color: '#21ad17'
                        },
                    }
                },
            },
            fontSize: {
                header: '30px'
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                nanum: "Nanum Pen Script",
                lexend: "Lexend"
            },
            fontWeight: {
              header: '900'
            },
            boxShadow: {
                'buttonBlue': '0 35px 60px -15px #2D1FDE',
                'cardShadow': '0px 4px 25px #27233c47',
            },
            flex: {
                'tags': '1 1 30%'
            },
            zIndex: {
                '100': '100',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography')
    ],
};
