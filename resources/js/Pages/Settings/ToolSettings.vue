<template>
    <app-layout title="Tooleinstellungen">
        <div class="max-w-screen-lg my-8 ml-20 mr-40">
            <div class="">
                <h2 class="font-bold font-lexend text-3xl my-2">Tooleinstellungen</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Definiere globale Einstellungen für dein ArtWork tool.
                </div>
            </div>
            <div class="mt-16 max-w-2xl">
                <h2 class="font-bold font-lexend text-xl my-2">Branding</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Damit dein ArtWork tool eindeutig deinem Unternehmen zugeordnet werden kann, lade hier deine eigenen
                    ArtWork tool Logos und deine Login-Illustration hoch.
                </div>
            </div>

            <label class="block text-sm font-medium text-primary">
                Logo groß
            </label>

            <div class="flex items-center">
                <div class="border-2 border-gray-300 border-dashed rounded-md p-2">
                    <img v-show="bigLogoPreview" :src="bigLogoPreview" alt="Logo"
                         class="rounded-md h-20 w-20 object-cover">

                    <svg v-show="!bigLogoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                         viewBox="0 0 48 48" aria-hidden="true">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <button
                    class=" inline-flex items-center px-4 ml-10 py-2 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    @click.prevent="selectNewBigLogo">
                    Großes Logo ändern
                </button>
                <input type="file" class="hidden"
                       ref="logo"
                       @change="updateBigLogoPreview">
            </div>

            <label class="block text-sm font-medium text-primary">
                Logo klein
            </label>

            <div class="flex items-center">
                <div class="border-2 border-gray-300 border-dashed rounded-md p-2">
                    <img v-show="smallLogoPreview" :src="smallLogoPreview" alt="Logo"
                         class="rounded-md h-20 w-20 object-cover">

                    <svg v-show="!smallLogoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                         viewBox="0 0 48 48" aria-hidden="true">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <button
                    class=" inline-flex items-center px-4 ml-10 py-2 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    @click.prevent="selectNewSmallLogo">
                    Kleines Logo ändern
                </button>
                <input type="file" class="hidden"
                       ref="logo"
                       @change="updateSmallLogoPreview">
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
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'


export default defineComponent({
    components: {
        AppLayout,
    },
    props: [],
    data() {
        return {
            bigLogoPreview: null,
            smallLogoPreview: null,
            bannerPreview: null,
            form: this.$inertia.form({
                _method: 'POST',
                logo: null,
                banner: null,
            })
        }
    },
    methods: {
        selectNewBigLogo() {
            this.$refs.bigLogo.click();
        },
        selectNewSmallLogo() {
            this.$refs.smallLogo.click();
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
        updateSmallLogoPreview() {
            const smallLogo = this.$refs.smallLogo.files[0];

            if (!smallLogo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.smallLogoPreview = e.target.result;
            }

            reader.readAsDataURL(smallLogo);
        },
        updateBigLogoPreview() {
            const bigLogo = this.$refs.bigLogo.files[0];

            if (!bigLogo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.bigLogoPreview = e.target.result;
            }

            reader.readAsDataURL(bigLogo);
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
    },
})
</script>
