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
                <div class="mt-1">
                    <input
                        v-model="form.password"
                        id="password" name="password" type="password" autocomplete="new-password" required placeholder="Passwort"
                        class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                </div>

            </div>
                <div class="sm:col-span-3">
                    <!-- TODO: HIER PASSWORTBALKEN + HINWEIS EINBAUEN -->
                </div>
            <div class="sm:col-span-3">
                <div class="mt-1">
                    <input
                        v-model="form.password_confirmation"
                        id="password_confirmation" name="password" type="password" autocomplete="new-password" required placeholder="Passwort wiederholen"
                        class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                </div>
            </div>
            </div>

            <!-- TODO: DAS HIER IN SYSTEMEINSTELLUNGEN ÜBERFÜHREN
            <label class="block text-sm font-medium text-primary">
                Logo
            </label>

            <div class="flex items-center">
                <div class="border-2 border-gray-300 border-dashed rounded-md p-2">
                    <img v-show="logoPreview" :src="logoPreview" alt="Logo"
                         class="rounded-md h-20 w-20 object-cover">

                    <svg v-show="!logoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                         viewBox="0 0 48 48" aria-hidden="true">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <button
                    class=" inline-flex items-center px-4 ml-10 py-2 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    @click.prevent="selectNewLogo">
                    Logo ändern
                </button>
                <input type="file" class="hidden"
                       ref="logo"
                       @change="updateLogoPreview">
                <jet-input-error :message="form.errors.photo" class="mt-2"/>
            </div>

            <div>
                <label class="block text-sm font-medium text-primary"> Banner </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div v-show="!bannerPreview" class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                             viewBox="0 0 48 48" aria-hidden="true">
                            <path
                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="mini-logo-upload"
                                   class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                <span>Hier hochladen</span>
                                <input id="mini-logo-upload" ref="banner" @change="updateBannerPreview"
                                       name="file-upload" type="file" class="sr-only"/>
                            </label>
                            <p class="pl-1">oder per drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                    </div>

                    <div class="cursor-pointer" @click="selectNewBanner">
                        <img v-show="bannerPreview" :src="bannerPreview" alt="Aktuelles Banner">
                    </div>


                </div>

            </div>

-->
            <div>

                <button type="submit"
                        class=" inline-flex items-center px-28 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        :class="[this.form.email === '' || this.form.password === '' || this.form.first_name === '' || this.form.last_name === '' || this.form.password_confirmation === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                        :disabled="this.form.email === '' || this.form.password === '' || this.form.first_name === '' || this.form.last_name === '' || this.form.password_confirmation === ''"
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
        SvgCollection
    },
    props: ['user'],
    data() {
        return {
            logoPreview: null,
            bannerPreview: null,
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

    methods: {
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
