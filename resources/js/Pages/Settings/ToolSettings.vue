<template>
    <app-layout>
        <div class="max-w-screen-xl ml-14 mr-40 mb-4">
            <div v-if="hasAdminRole() || $canAny(['change tool settings'])">
                <h2 class="headline1 mb-2">Tooleinstellungen</h2>
                <div class="headline3Light">
                    Definiere globale Einstellungen für dein artwork.
                </div>
            </div>

            <form v-if="hasAdminRole() || $canAny(['change tool settings'])" @submit.prevent="changeLogos">
                <div class="mt-12 max-w-2xl">
                    <h2 class="headline2 my-2">Branding</h2>
                    <div class="xsLight">
                        Damit dein artwork eindeutig deinem Unternehmen zugeordnet werden kann, lade hier deine
                        eigenen
                        artwork Logos und deine Login-Illustration hoch.
                    </div>
                </div>

                <jet-input-error :message="uploadDocumentFeedback"/>

                <label class="block mt-6 mb-4 xsDark">
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
                        <div class="flex my-auto h-40 items-center xsLight"
                             v-if="$page.props.big_logo === null && bigLogoPreview === null">
                            Ziehe hier dein großes <br/> artwork Logo hin
                        </div>
                        <div class="cursor-pointer" v-else-if="!bigLogoPreview">
                            <img :src="$page.props.big_logo" alt="Logo"
                                 class="rounded-md h-40 w-40">
                        </div>
                    </div>

                    <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                        <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                        <span
                            class="ml-2 my-auto hind">Lade dein Logo als .svg, .png, .gif oder .jpg hoch.
                            Das Logo wird z.B. auf der Login-Seite und in der gesamten E-Mail-Kommunikation genutzt.
                        </span>
                    </div>
                </div>

                <label class="block mt-12 mb-4 xsDark">
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
                        <div class="xsLight flex my-auto h-40 items-center"
                             v-if="$page.props.small_logo === null && smallLogoPreview === null">
                            Ziehe hier dein kleines <br/> artwork Logo hin

                        </div>
                        <div class="cursor-pointer" v-else-if="!smallLogoPreview">
                            <img :src="$page.props.small_logo" alt="Logo"
                                 class="rounded-md h-40 w-40">

                        </div>
                    </div>
                    <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                        <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                        <span
                            class="hind ml-2 my-auto">Lade dein Logo als .svg, .png, .gif oder .jpg hoch.
                            Das Logo wird z.B. in der Sidebar genutzt.
                        </span>
                    </div>
                </div>

                <label class="block mt-12 mb-4 xsDark">
                    Login-Illustration </label>
                <div class="grid grid-cols-6 gap-x-12 items-center">
                    <div
                        class="flex col-span-2 w-full justify-center border-2 bg-stone-50 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @click="selectNewBanner"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedBanner($event)">
                        <div v-show="!bannerPreview" class="space-y-1 text-center">
                            <div class="xsLight flex my-auto h-40 items-center"
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
                    <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                        <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                        <span
                            class="ml-2 my-auto hind">Lade deine Illustration als .svg, .png, .gif oder .jpg hoch.
                                Die Illustration wird auf der Login-Seite genutzt.</span>
                    </div>
                </div>

                <div class="mt-6 items-center">
                    <AddButton @click.prevent="changeLogos"
                               text="Änderungen speichern" mode="modal"/>
                </div>
            </form>

            <div v-if="hasAdminRole() || $canAny(['change tool settings'])">
                <div class="mt-20">
                    <h2 class="headline2">Kommunikation & Rechtliches</h2>
                    <div class="xsLight mt-4">
                        Definiere hier den Footer-Text für sämtliche System-E-Mails und gib' die Links zur
                        Impressum- und Datenschutzseite deines Unternehmens an. Darüber hinaus kannst du eine
                        E-Mail Adresse definieren die beim versenden von E-Mails verwendet wird.
                    </div>
                    <div class="mt-4">
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <inputComponent
                                    v-model="mailForm.businessName"
                                    placeholder="Unsere Organisation"
                                />
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <inputComponent v-model="mailForm.impressumLink" placeholder="Link zum Impressum"/>
                                <span v-if="showInvalidImpressumLinkErrorText"
                                      class="errorText">
                                    Keine gültige URL (Beispiel: http://google.de)
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <inputComponent v-model="mailForm.privacyLink" placeholder="Link zum Datenschutz"/>
                                <span v-if="showInvalidPrivacyLinkErrorText"
                                      class="errorText">
                                    Keine gültige URL (Beispiel: http://google.de)
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-3">
                                <inputComponent
                                    v-model="mailForm.businessEmail"
                                    placeholder="Geschäfts-E-Mail-Adresse"
                                />
                                <span v-if="showInvalidBusinessEmailAddressErrorText"
                                      class="errorText">
                                    Keine gültige E-Mail Adresse
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 col-span-9 grid grid-cols-9">
                            <div class="sm:col-span-8">
                                <textarea
                                    placeholder="E-Mail-Footer"
                                    v-model="mailForm.emailFooter"
                                    rows="4"
                                    class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-2 w-full placeholder:xsLight border-2 border-gray-300 "/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 items-center">
                    <AddButton @click.prevent="changeEmailData"
                               text="Änderungen speichern" mode="modal"/>
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
import AddButton from "@/Layouts/Components/AddButton";
import InputComponent from "@/Layouts/Components/InputComponent";
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import JetDialogModal from "@/Jetstream/DialogModal";
import Permissions from "@/mixins/Permissions.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({
    mixins: [Permissions],
    components: {
        AddButton,
        AppLayout,
        SvgCollection,
        JetInputError,
        InputComponent,
        CheckIcon,
        XIcon,
        JetDialogModal
    },
    props: [],
    data() {
        return {
            uploadDocumentFeedback: "",
            bigLogoPreview: null,
            smallLogoPreview: null,
            bannerPreview: null,
            form: useForm({
                _method: 'PUT',
                bigLogo: null,
                smallLogo: null,
                banner: null,
            }),
            mailForm: useForm({
                businessName: this.$page.props.businessName,
                impressumLink: this.$page.props.impressumLink,
                privacyLink: this.$page.props.privacyLink,
                emailFooter: this.$page.props.emailFooter,
                businessEmail: this.$page.props.businessEmail
            }),
            showInvalidBusinessEmailAddressErrorText: false,
            showInvalidImpressumLinkErrorText: false,
            showInvalidPrivacyLinkErrorText: false
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
            this.form.post(route('tool.update'));
        },
        changeEmailData() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            this.showInvalidBusinessEmailAddressErrorText =
                this.mailForm.businessEmail !== '' && !emailRegex.test(this.mailForm.businessEmail);

            const urlRegex = /^http:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(:[0-9]+)?(\/[^]*)?$/;
            this.showInvalidImpressumLinkErrorText =
                this.mailForm.impressumLink !== '' && !urlRegex.test(this.mailForm.impressumLink);
            this.showInvalidPrivacyLinkErrorText =
                this.mailForm.privacyLink !== '' && !urlRegex.test(this.mailForm.privacyLink);

            if (
                this.showInvalidBusinessEmailAddressErrorText ||
                this.showInvalidImpressumLinkErrorText ||
                this.showInvalidPrivacyLinkErrorText
            ) {
                return;
            }

            this.mailForm.patch(route('tool.updateMail'));
        },
    },
})
</script>
