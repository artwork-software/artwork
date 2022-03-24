<template>
    <Head title="Register"/>

    <jet-authentication-card>
        <template #AuthHeading>
            <h2 class="mt-6 text-center text-4xl font-bold text-gray-900">
                Account erstellen
            </h2>
        </template>

        <jet-validation-errors class="mb-4"/>
        <div class="py-8 px-4">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="text-2xl font-bold text-black">
                    <p>ArtWork.tools</p>
                </div>
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700">
                        Name
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.name"
                            id="name" name="name" type="text" autocomplete="name" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700">
                        E-Mail-Adresse
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.email"
                            id="email" name="email" type="email" autocomplete="email" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="business" class="block text-sm font-bold text-gray-700">
                        Unternehmen
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.business"
                            id="business" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="position" class="block text-sm font-bold text-gray-700">
                        Position
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.position"
                            id="position" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="phoneNumber" class="block text-sm font-bold text-gray-700">
                        Telefonnummer
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.phone_number"
                            id="phoneNumber" type="text"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700">
                        Passwort
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.password"
                            id="password" name="password" type="password" autocomplete="new-password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700">
                        Passwort wiederholen
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.password_confirmation"
                            id="password_confirmation" name="password" type="password" autocomplete="new-password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" />
                    </div>
                </div>

                <label class="block text-sm font-medium text-gray-700">
                    Logo
                </label>

                <div class="flex items-center">
                    <div class="border-2 border-gray-300 border-dashed rounded-md p-2">
                        <img v-show="logoPreview" :src="logoPreview" alt="Logo"
                             class="rounded-md h-20 w-20 object-cover">

                        <svg v-show="!logoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <jet-secondary-button class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" type="button" @click.prevent="selectNewLogo">
                        Logo ändern
                    </jet-secondary-button>
                    <input type="file" class="hidden"
                           ref="logo"
                           @change="updateLogoPreview">
                    <jet-input-error :message="form.errors.photo" class="mt-2"/>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"> Banner </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div v-show="!bannerPreview" class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="mini-logo-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                    <span>Hier hochladen</span>
                                    <input id="mini-logo-upload" ref="banner" @change="updateBannerPreview" name="file-upload" type="file" class="sr-only" />
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


                <div>

                    <button type="submit"
                            class="w-full flex justify-center
                    py-3 px-4 border border-transparent rounded-md shadow-sm
                    text-sm font-bold text-white
                    bg-blue-500 hover:bg-dark-primary
                    focus:outline-none focus:ring-2 focus:ring-offset-2
                    focus:ring-primary"
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                    >
                        Registrieren
                    </button>
                </div>
            </form>
        </div>
    </jet-authentication-card>
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
    },
    props: ['user'],
    data() {
        return {
            logoPreview: null,
            bannerPreview: null,
            form: this.$inertia.form({
                name: '',
                email: '',
                business:'',
                position:'',
                phone_number:'',
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

            if(!banner) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.bannerPreview = e.target.result;
            }

            reader.readAsDataURL(banner);
        },
        updateLogoPreview() {
            const logo = this.$refs.logo.files[0];

            if(!logo) return;

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
