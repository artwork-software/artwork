const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require("tailwindcss/colors")

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
        "./index.html",
        "./node_modules/vue-tailwind-datepicker/**/*.js"
    ],
    theme: {
        extend: {
            colors: {
                primary: '#222', //'#27233C',
                primaryHover:'#372F60',
                success:'#1FC687',
                error:'#FD6D73',
                secondary:'#A7A6B1',
                calendarText:'#333',
                secondaryHover:'#FCFCFB',
                darkInputBg:'rgba(252,252,251,0.15)',
                darkInputText: '#D8D7DE',
                help: '#93929D',
                darkGray: '#616069',
                darkGrayBg: 'rgba(255,255,255,0.15)',
                backgroundGray: '#F5F5F3',
                backgroundGray2:'#e3e3e3',
                buttonBlue:'#3017AD',
                buttonHover:'#2D1FDE',
                tagBg: 'rgba(48,23,173,0.1)',
                tagBgGray: 'rgba(94,92,102,1)',
                tagBgGreen: 'rgba(48,217,0,0.1)',
                tag: 'rgba(48,23,173,0.3)',
                tagText: 'rgba(48,23,173,1)',
                tagTextGreen: 'rgba(48,80,0,1)',
                backgroundBlue: '#3017AD34',
                lightBackgroundGray: '#F4F4F3',
                checkBoxBg: '#474459',
                silverGray:'#CECDD8',
                userBg: '#EDEDEC',
                colorOfAction: '#e4eef5',
                menuButtonBlue: '#3017AD',
                shiftText: '#82818A',
                linkOnDarkColor: '#BDB6F0',
                "vtd-primary": colors.sky,
                "vtd-secondary": colors.gray,
                artwork: {
                    navigation: {
                        background: '#222',
                        color: '#eee'
                    },
                    messages: {
                        success: '#25cd0e',
                        waring: '#ecce00',
                        error: '#ef4444',
                        info: '#a7a6b1'
                    },
                    buttons: {
                        context: '#6f6f6f',
                        create: '#3073ae',
                        default: '#2a3d75',
                        hover: '#1c77d7',
                        update: '#2a3d75'
                    },
                    context: {
                        light: '#e8e8e8',
                        dark: '#6f6f6f'
                    },
                    project: {
                        background: '#eee'
                    },
                    icons: {
                        default: {
                            background: 'rgba(48,115,174,0.1)',
                            color: '#3073ae'
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
                buttonBlue: '0 35px 60px -15px #2D1FDE',
                cardShadow: '0px 4px 25px #27233c47',
                artworkCard: '0 3px 10px rgb(0,0,0,0.2)',
                glass: 'inset 0px 0.5px 0px 0px hsla(0, 0%, 100%, 0.2), inset 0px 0px 8px 2px hsla(0, 0%, 100%, 0.12), 0px 0.5px 2px 0px rgba(0, 0, 0, 0.08), 0px 2px 16px -4px rgba(0, 0, 0, 0.1)',
                elevationTwo: 'inset 0px 0.5px 0px 0px hsla(0, 0%, 100%, 0.2), inset 0px 0px 8px 2px hsla(0, 0%, 100%, 0.12), 0px 0.5px 2px 0px rgba(0, 0, 0, 0.08), 0px 2px 16px -4px rgba(0, 0, 0, 0.1)', // optional gleiche wie glass
                buttonGlowLight: '0 0 6px 2px rgba(255, 255, 255, 0.6)', // optional
            },
            backdropBlur: {
                27: '27px',
                '2xl': '40px',
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
