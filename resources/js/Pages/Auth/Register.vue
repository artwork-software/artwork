<template>
    <div class="py-8 px-64">
        <form class="space-y-6" @submit.prevent="submit">
            <div class="text-2xl font-bold text-black">
                <p>ArtWork.tools</p>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">Registrierung</h2>
                <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.first_name"
                            id="first_name" name="name" type="text" required placeholder="Vorname"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.last_name"
                            id="last_name" name="name" type="text" required placeholder="Name"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.email"
                            id="email" name="email" type="email" autocomplete="email" required placeholder="E-Mail"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.phone_number"
                            id="phoneNumber" type="text" placeholder="Telefonnummer"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.business"
                            id="business" type="text" required placeholder="Unternehmen"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.position"
                            id="position" type="text" required placeholder="Position"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>

            </div>

            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input
                            v-model="form.password"
                            id="password_confirmation1" name="password" type="password"
                            autocomplete="new-password" required placeholder="Neues Passwort"
                            :class="[form.hasErrors ? 'border-error' : 'border-gray-200',
                                    'placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm']"/>
                        <div v-if="form.hasErrors"
                             class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <XIcon class="h-5 w-5 text-error" aria-hidden="true"/>
                        </div>
                    </div>
                    <jet-input-error :message="form.errors.password" class="mt-2"/>

                </div>
                <div v-if="form.password.length>0" class="sm:col-span-3 flex items-center">

                    <span class="text-xs text-secondary">Schwach</span>

                    <div class="mx-6 mt-1 w-full bg-gray-200 h-1 dark:bg-gray-700">
                        <div :class="[pw_feedback < 1
                                ? 'bg-error'
                                : pw_feedback < 3
                                ? 'bg-amber-400' :
                                'bg-success' ,
                                'h-1']" :style="{width: `${(pw_feedback + 1) / 5 * 100}%`}"></div>
                    </div>

                    <span class="text-xs">Stark</span>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <div class="mt-1 relative rounded-md shadow-sm">

                            <input
                                v-model="form.password_confirmation"
                                id="password_confirmation2" name="password" type="password"
                                autocomplete="new-password" required placeholder="Neues Passwort wiederholen"
                                :class="[form.hasErrors ? 'border-error' : 'border-gray-200',
                                    'placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm']"
                            />
                            <div v-if="form.hasErrors"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <XIcon class="h-5 w-5 text-error" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                    <jet-input-error :message="form.errors.password_confirmation" class="mt-2"/>
                </div>

                <div class="sm:col-span-3 relative">

                    <div class="absolute -mt-4 flex items-center">
                        <div>
                            <SvgCollection svgName="arrowTopLeft"/>
                        </div>

                        <span class="leading-tight font-nanum text-secondary ml-1 my-auto">Das Passwort muss mind. 10 Zeichen lang sein,
                                    mind. 1 Ziffer und Groß- und Kleinbuchstaben und Sonderzeichen beinhalten.</span>
                    </div>

                </div>
            </div>

            <div>

                <button type="submit"
                        class=" inline-flex items-center px-28 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        :class="[form.email === '' || form.password === '' || form.first_name === '' || form.last_name === '' || form.password_confirmation === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                        :disabled="form.email === '' || form.password === '' || form.first_name === '' || form.last_name === '' || form.password_confirmation === ''"
                >
                    Registrieren
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import JetAuthenticationCard from '@/Jetstream/AuthenticationCard.vue'
import JetAuthenticationCardLogo from '@/Jetstream/AuthenticationCardLogo.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetCheckbox from '@/Jetstream/Checkbox.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'
import {Head, Link} from '@inertiajs/inertia-vue3';
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon} from "@heroicons/vue/solid";


export default defineComponent({
    components: {
        Head,
        JetAuthenticationCard,
        JetAuthenticationCardLogo,
        JetButton,
        JetInput,
        JetCheckbox,
        JetLabel,
        JetValidationErrors,
        Link,
        SvgCollection,
        JetInputError,
        XIcon
    },
    props: ['user'],
    data() {
        return {
            logoPreview: null,
            bannerPreview: null,
            pw_feedback: 0,
            form: this.$inertia.form({
                _method: 'POST',
                first_name: '',
                last_name: '',
                email: '',
                business: '',
                position: '',
                phone_number: '',
                password: '',
                password_confirmation: '',
                logo: null,
                banner: null,
            })
        }
    },
    watch: {
        'form.password': {
            handler() {
                if(this.form.password.length > 0) {
                    this.password_feedback()
                }
            },
            deep: true
        },
        'form.email': {
            handler() {
                if(this.form.email.length > 0) {
                    this.validate_email()
                }
            },
            deep: true
        },
    },
    methods: {
        validate_email() {
            axios.get('/email', {
                params: {
                    email: this.form.email
                }
            }).then( response => {
                console.log(response.data)
            })
        },
        password_feedback() {
            axios.get('/password_feedback', {
                params: {
                    password: this.form.password
                }
            }).then( response => {
                this.pw_feedback = response.data
            })
        },
        selectNewLogo() {
            this.$refs.logo.click();
        },
        selectNewBanner() {
            this.$refs.banner.click();
        },
        updateBannerPreview() {
            const banner = this.$refs.banner.files[0];

            if (!banner) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.bannerPreview = e.target.result;
            }

            reader.readAsDataURL(banner);
        },
        updateLogoPreview() {
            const logo = this.$refs.logo.files[0];

            if (!logo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.logoPreview = e.target.result;
            }

            reader.readAsDataURL(logo);
        },
        submit() {
            if (this.$refs.logo) {
                this.form.logo = this.$refs.logo.files[0]
            }
            if (this.$refs.banner) {
                this.form.banner = this.$refs.banner.files[0]
            }
            this.form.post(this.route('setup.create'), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            })
        }
    }
})
</script>
