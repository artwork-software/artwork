<template>
    <app-layout title="Tooleinstellungen">
        <form @submit.prevent="submit">
            <div class="max-w-screen-xl my-8 ml-20 mr-40">

                <div class="">
                    <h2 class="font-bold font-lexend text-3xl mb-2">Tooleinstellungen</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Definiere globale Einstellungen für dein ArtWork tool.
                    </div>
                </div>
                <div class="mt-6 max-w-2xl">
                    <h2 class="font-bold font-lexend text-xl my-2">Branding</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Damit dein ArtWork tool eindeutig deinem Unternehmen zugeordnet werden kann, lade hier deine
                        eigenen
                        ArtWork tool Logos und deine Login-Illustration hoch.
                    </div>
                </div>

                <label class="block mt-6 mb-4 text-sm font-medium text-secondary subpixel-antialiased">
                    Logo groß (Upload per Klick)
                </label>

                <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div class="flex col-span-2 justify-center border-2 w-80 cursor-pointer border-gray-300 border-dashed rounded-md p-2" @click="selectNewBigLogo">
                        <img v-show="bigLogoPreview" :src="bigLogoPreview" alt="Logo"
                             class="rounded-md h-40 w-40 object-cover">
                        <input type="file" class="hidden"
                               ref="bigLogo"
                               @change="updateBigLogoPreview">
                        <div v-if="$page.props.big_logo === null">
                        <svg v-show="!bigLogoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                             fill="none"
                             viewBox="0 0 48 48" aria-hidden="true">
                            <path
                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        </div>
                        <div class="cursor-pointer" v-else-if="!bigLogoPreview" >
                            <img :src="$page.props.big_logo" alt="Logo"
                                 class="rounded-md h-40 w-40 object-cover">
                            <input type="file" class="hidden"
                                   ref="bigLogo"
                                   @change="updateBigLogoPreview">
                        </div>
                    </div>
                    <div v-if="$page.props.can.show_hints" class="col-span-4 items-center flex">
                        <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                        <span
                            class="font-nanum text-secondary tracking-tight ml-2 my-auto tracking-tight text-xl">Lade dein Logo als .svg, .png, .gif oder .jpg hoch.
                            Das Logo wird z.B. auf der Login-Seite und in der gesamten E-Mail-Kommunikation genutzt.
                        </span>
                    </div>
                </div>

                <label class="block mt-4 mb-4 text-sm font-medium text-secondary subpixel-antialiased">
                    Logo klein (Upload per Klick)
                </label>
                <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div class="flex col-span-2 justify-center border-2 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2" @click="selectNewSmallLogo">
                        <img v-show="smallLogoPreview" :src="smallLogoPreview" alt="Logo"
                             class="rounded-md h-40 w-40 object-cover">
                        <input type="file" class="hidden"
                               ref="smallLogo"
                               @change="updateSmallLogoPreview">
                        <div v-if="$page.props.small_logo === null">
                        <svg v-show="!smallLogoPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                             fill="none"
                             viewBox="0 0 48 48" aria-hidden="true">
                            <path
                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                            <input type="file" class="hidden"
                                   ref="smallLogo"
                                   @change="updateSmallLogoPreview">
                        </div>
                        <div class="cursor-pointer" v-else-if="!smallLogoPreview">
                            <img :src="$page.props.small_logo" alt="Logo"
                                 class="rounded-md h-40 w-40 object-cover">

                        </div>
                    </div>
                    <div v-if="$page.props.can.show_hints" class="col-span-4 items-center flex">
                        <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                        <span
                            class="font-nanum text-secondary tracking-tight ml-2 my-auto tracking-tight text-xl">Lade dein Logo als .svg, .png, .gif oder .jpg hoch.
                            Das Logo wird z.B. in der Sidebar genutzt.
                        </span>
                    </div>
                </div>
                <div>

                    <label class="block mt-4 mb-4 text-sm font-medium text-secondary subpixel-antialiased"> Illustration (Upload per Klick) </label>
                    <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div class="flex col-span-2 w-full justify-center border-2 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2" @click="selectNewBanner">
                        <div v-show="!bannerPreview" class="space-y-1 text-center">
                            <div v-if="$page.props.banner === null">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                     viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input id="banner-upload" ref="banner" @change="updateBannerPreview"
                                       name="file-upload" type="file" class="sr-only"/>
                            </div>
                            <div class="cursor-pointer" v-else>
                                <img :src="$page.props.banner" alt="Aktuelles Banner"
                                     class="rounded-md h-40 w-40 object-cover">
                                <input type="file" class="hidden"
                                       ref="banner"
                                       @change="updateBannerPreview">
                            </div>
                        </div>
                        <div class="cursor-pointer">
                            <img v-show="bannerPreview" :src="bannerPreview" alt="Aktuelles Banner" class="rounded-md h-40 w-40 object-cover">
                            <input type="file" class="hidden"
                                   ref="banner"
                                   @change="updateBannerPreview">
                        </div>
                    </div>
                        <div v-if="$page.props.can.show_hints" class="col-span-4 items-center flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-2 my-auto tracking-tight text-xl">Lade deine Illustration als .svg, .png, .gif oder .jpg hoch.
                                Die Illustration wird auf der Login-Seite genutzt.</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 items-center sm:grid-cols-6">
                        <button type="submit" class="sm:col-span-2 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent
                                        font-bold text-lg uppercase shadow-sm text-secondaryHover">
                            Änderungen speichern
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection";


export default defineComponent({
    components: {
        AppLayout,
        SvgCollection
    },
    props: [],
    data() {
        return {
            bigLogoPreview: null,
            smallLogoPreview: null,
            bannerPreview: null,
            form: this.$inertia.form({
                _method: 'PUT',
                bigLogo: null,
                smallLogo: null,
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

            if (this.$refs.bigLogo) {
                this.form.bigLogo = this.$refs.bigLogo.files[0]
            }
            if (this.$refs.smallLogo) {
                this.form.smallLogo = this.$refs.smallLogo.files[0]
            }
            if (this.$refs.banner) {
                this.form.banner = this.$refs.banner.files[0]
            }

            this.form.post(route('tool.update'))

        }
    },
})
</script>
