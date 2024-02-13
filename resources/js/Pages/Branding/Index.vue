<template>
    <ToolSettingsHeader>
        <form @submit.prevent="changeLogos">
            <div v-if="this.$page.props.flash.success"
                 class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
                {{ this.$page.props.flash.success }}
            </div>
            <div class="max-w-2xl">
                <h2 class="headline2 my-2">Branding</h2>
                <div class="xsLight">
                    Damit dein artwork eindeutig deinem Unternehmen zugeordnet werden kann, lade hier deine eigenen
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
                    <img v-show="bigLogoPreview" :src="bigLogoPreview" alt="Logo" class="rounded-md h-40 w-40">
                    <input type="file" class="hidden"
                           ref="bigLogo"
                           @change="updateBigLogoPreview">
                    <div class="flex my-auto h-40 items-center xsLight"
                         v-if="$page.props.big_logo === null && bigLogoPreview === null">
                        Ziehe hier dein großes <br/> artwork Logo hin
                    </div>
                    <div class="cursor-pointer" v-else-if="!bigLogoPreview">
                        <img :src="$page.props.big_logo" alt="Logo" class="rounded-md h-40 w-40">
                    </div>
                </div>
                <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                    <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                    <span class="ml-2 my-auto hind">
                        Lade dein Logo als .svg, .png, .gif oder .jpg hoch. Das Logo wird z.B. auf der Login-Seite
                        und in der gesamten E-Mail-Kommunikation genutzt.
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
                    <img v-show="smallLogoPreview" :src="smallLogoPreview" alt="Logo" class="rounded-md h-40 w-40">
                    <input type="file" class="hidden" ref="smallLogo" @change="updateSmallLogoPreview">
                    <div class="xsLight flex my-auto h-40 items-center"
                         v-if="$page.props.small_logo === null && smallLogoPreview === null">
                        Ziehe hier dein kleines <br/> artwork Logo hin
                    </div>
                    <div class="cursor-pointer" v-else-if="!smallLogoPreview">
                        <img :src="$page.props.small_logo" alt="Logo" class="rounded-md h-40 w-40">
                    </div>
                </div>
                <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                    <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                    <span class="hind ml-2 my-auto">
                        Lade dein Logo als .svg, .png, .gif oder .jpg hoch. Das Logo wird z.B. in der Sidebar genutzt.
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
                            <input id="banner-upload"
                                   ref="banner"
                                   @change="updateBannerPreview"
                                   name="file-upload"
                                   type="file"
                                   class="sr-only"
                            />
                        </div>
                        <div class="cursor-pointer" v-else>
                            <img :src="$page.props.banner" alt="Aktuelles Banner" class="rounded-md h-40 w-40">
                        </div>
                    </div>
                    <div class="cursor-pointer">
                        <img v-show="bannerPreview"
                             :src="bannerPreview"
                             alt="Aktuelles Banner"
                             class="rounded-md h-40 w-40"
                        >
                        <input type="file" class="hidden"
                               ref="banner"
                               @change="updateBannerPreview">
                    </div>
                </div>
                <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                    <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                    <span class="ml-2 my-auto hind">
                        Lade deine Illustration als .svg, .png, .gif oder .jpg hoch. Die Illustration wird auf
                        der Login-Seite genutzt.
                    </span>
                </div>
            </div>
            <div class="mt-6 items-center">
                <AddButton @click.prevent="changeLogos" text="Änderungen speichern" mode="modal"/>
            </div>
        </form>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({
    components: {
        JetInputError,
        AddButton,
        SvgCollection,
        AppLayout,
        ToolSettingsHeader
    },
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
                        this.form.bigLogo = file;
                    }

                    if (type === 'smallLogo') {
                        this.smallLogoPreview = e.target.result;
                        this.form.smallLogo = file;
                    }

                    if (type === 'banner') {
                        this.bannerPreview = e.target.result;
                        this.form.banner = file;
                    }
                }

                reader.readAsDataURL(file);
            } else {
                this.uploadDocumentFeedback = "Es werden ausschließlich Logos und Illustrationen vom Typ .jpeg, " +
                    ".svg, .png und .gif akzeptiert.";
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
            this.form.post(route('tool.branding.update'));
        }
    },
});
</script>