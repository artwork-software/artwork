<template>
    <app-layout>

        <div class="max-w-screen-xl my-8 ml-20 mr-40">

            <div class="">
                <h2 class="font-black font-lexend text-3xl mb-2">Tooleinstellungen</h2>
                <div class="text-secondary subpixel-antialiased tracking-tight leading-6 sub">
                    Definiere globale Einstellungen für dein ArtWork tool.
                </div>
            </div>

            <form @submit.prevent="changeLogos">
                <div class="mt-32 max-w-2xl">
                    <h2 class="font-bold font-lexend text-xl my-2">Branding</h2>
                    <div class="text-secondary subpixel-antialiased tracking-tight leading-6 sub">
                        Damit dein ArtWork tool eindeutig deinem Unternehmen zugeordnet werden kann, lade hier deine
                        eigenen
                        ArtWork tool Logos und deine Login-Illustration hoch.
                    </div>
                </div>

                <jet-input-error :message="uploadDocumentFeedback"/>

                <label class="block mt-6 mb-4 text-sm font-medium text-secondary subpixel-antialiased">
                    Logo groß (Upload per Klick oder Drag & Drop)
                </label>

                <div class="grid grid-cols-6 gap-x-12 items-center">

                    <div
                        class="flex col-span-2 justify-center border-2 w-80 cursor-pointer border-gray-300 bg-stone-50 border-dashed rounded-md p-2"
                        @click="selectNewBigLogo"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedBigLogo($event)">
                        <img v-show="bigLogoPreview" :src="bigLogoPreview" alt="Logo"
                             class="rounded-md h-40 w-40">
                        <input type="file" class="hidden"
                               ref="bigLogo"
                               @change="updateBigLogoPreview">
                        <div class="text-sm text-secondary flex my-auto h-40 items-center"
                             v-if="$page.props.big_logo === null && bigLogoPreview === null">
                            Ziehe hier dein großes <br/> ArtWork tool Logo hin
                        </div>
                        <div class="cursor-pointer" v-else-if="!bigLogoPreview">
                            <img :src="$page.props.big_logo" alt="Logo"
                                 class="rounded-md h-40 w-40">
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

                <label class="block mt-12 mb-4 text-sm font-medium text-secondary subpixel-antialiased">
                    Logo klein (Upload per Klick oder Drag & Drop)
                </label>
                <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div
                        class="flex col-span-2 justify-center border-2 bg-stone-50 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @click="selectNewSmallLogo"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedSmallLogo($event)">
                        <img v-show="smallLogoPreview" :src="smallLogoPreview" alt="Logo"
                             class="rounded-md h-40 w-40">
                        <input type="file" class="hidden"
                               ref="smallLogo"
                               @change="updateSmallLogoPreview">
                        <div class="text-sm text-secondary flex my-auto h-40 items-center"
                             v-if="$page.props.small_logo === null && smallLogoPreview === null">
                            Ziehe hier dein kleines <br/> ArtWork tool Logo hin

                        </div>
                        <div class="cursor-pointer" v-else-if="!smallLogoPreview">
                            <img :src="$page.props.small_logo" alt="Logo"
                                 class="rounded-md h-40 w-40">

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

                <label class="block mt-12 mb-4 text-sm font-medium text-secondary subpixel-antialiased">
                    Login-Illustration </label>
                <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div
                        class="flex col-span-2 w-full justify-center border-2 bg-stone-50 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @click="selectNewBanner"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedBanner($event)">
                        <div v-show="!bannerPreview" class="space-y-1 text-center">
                            <div class="text-sm text-secondary flex my-auto h-40 items-center"
                                 v-if="$page.props.banner === null && bannerPreview === null">
                                Ziehe hier deine <br/> Login-Illustration hin
                                <input id="banner-upload" ref="banner" @change="updateBannerPreview"
                                       name="file-upload" type="file" class="sr-only"/>
                            </div>
                            <div class="cursor-pointer" v-else>
                                <img :src="$page.props.banner" alt="Aktuelles Banner"
                                     class="rounded-md h-40 w-40">
                            </div>
                        </div>
                        <div class="cursor-pointer">
                            <img v-show="bannerPreview" :src="bannerPreview" alt="Aktuelles Banner"
                                 class="rounded-md h-40 w-40">
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

                <div class="mt-6 grid gap-y-4 gap-x-4 items-center grid-cols-9">
                    <button class="col-span-3 py-2.5 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent
                                        font-bold text-lg uppercase shadow-sm text-secondaryHover">
                        Änderungen speichern
                    </button>
                </div>
            </form>

            <div>

                <div class="mt-20">
                    <h2 class="font-bold font-lexend text-xl mb-2">Kommunikation & Rechtliches</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Definiere hier den Footer-Text für sämtliche System-E-Mails und gib' die Links zur
                        Impressum- und Datenschutzseite deines Unternehmens an.
                    </div>
                    <div class="mt-8">
                        <div class="col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <div class="mt-1">
                                    <input type="text" v-model="mailForm.impressumLink"
                                           placeholder="Link zum Impressum"
                                           class="text-primary placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border-gray-300 "/>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <div class="mt-1">
                                    <input type="text" v-model="mailForm.privacyLink"
                                           placeholder="Link zum Datenschutz"
                                           class="text-primary placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border-gray-300 "/>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-8">
                                            <textarea
                                                placeholder="E-Mail-Footer"
                                                v-model="mailForm.emailFooter" rows="4"
                                                class="resize-none placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border border-gray-300 "/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 items-center sm:grid-cols-6">
                    <button @click.prevent="changeEmailData" class="sm:col-span-2 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent
                                        font-bold text-lg uppercase shadow-sm text-secondaryHover">
                        Änderungen speichern
                    </button>
                </div>

            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetInputError from "@/Jetstream/InputError";

export default defineComponent({
    components: {
        AppLayout,
        SvgCollection,
        JetInputError
    },
    props: [],
    data() {
        return {
            uploadDocumentFeedback: "",
            bigLogoPreview: null,
            smallLogoPreview: null,
            bannerPreview: null,
            form: this.$inertia.form({
                _method: 'PUT',
                bigLogo: null,
                smallLogo: null,
                banner: null,
            }),
            mailForm: this.$inertia.form({
                _method: 'PUT',
                impressumLink: this.$page.props.impressumLink,
                privacyLink: this.$page.props.privacyLink,
                emailFooter: this.$page.props.emailFooter,
            })
        }
    },
    methods: {
        validateTypeAndUpload(file, type) {
            this.uploadDocumentFeedback = "";

            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {

                const reader = new FileReader();

                reader.onload = (e) => {

                    if (type === 'bigLogo') {
                        this.bigLogoPreview = e.target.result;
                        this.form.bigLogo = file
                    }

                    if (type === 'smallLogo') {
                        this.smallLogoPreview = e.target.result;
                        this.form.smallLogo = file
                    }

                    if (type === 'banner') {
                        this.bannerPreview = e.target.result;
                        this.form.banner = file
                    }

                }

                reader.readAsDataURL(file);

            } else {
                this.uploadDocumentFeedback = "Es werden ausschließlich Logos und Illustrationen vom Typ .jpeg, .svg, .png und .gif akzeptiert."
            }


        },
        uploadDraggedBigLogo(event) {
            this.validateTypeAndUpload(event.dataTransfer.files[0], 'bigLogo');
        },
        uploadDraggedSmallLogo(event) {
            this.validateTypeAndUpload(event.dataTransfer.files[0], 'smallLogo');
        },
        uploadDraggedBanner(event) {
            this.validateTypeAndUpload(event.dataTransfer.files[0], 'banner');
        },
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
            this.validateTypeAndUpload(this.$refs.banner.files[0], 'banner');
        },
        updateSmallLogoPreview() {
            this.validateTypeAndUpload(this.$refs.smallLogo.files[0], 'smallLogo');
        },
        updateBigLogoPreview() {
            this.validateTypeAndUpload(this.$refs.bigLogo.files[0], 'bigLogo');
        },
        changeLogos() {
            this.form.post(route('tool.update'))
        },
        changeEmailData() {
            this.mailForm.post(route('tool.updateMail'))
        }
    },
})
</script>
