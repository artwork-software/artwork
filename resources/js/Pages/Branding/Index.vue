<template>
    <ToolSettingsHeader :title="$t('Branding')">
        <form @submit.prevent="changeLogos">
            <div v-if="this.$page.props.flash.success"
                 class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
                {{ this.$page.props.flash.success }}
            </div>
            <div class="max-w-2xl">
                <h2 class="headline2 my-2">{{ $t('Branding') }}</h2>
                <div class="xsLight">
                    {{ $t('To ensure your artwork is clearly associated with your company, please upload your custom artwork logos and login illustration here.') }}
                </div>
            </div>
            <jet-input-error :message="uploadDocumentFeedback"/>
            <label class="block mt-6 mb-4 xsDark">
                {{ $t('Big Logo (Upload by click or drag & drop)') }}
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
                        {{ $t('Drag your large artwork logo here') }}
                    </div>
                    <div class="cursor-pointer" v-else-if="!bigLogoPreview">
                        <img :src="$page.props.big_logo" alt="Logo" class="rounded-md h-40 w-40">
                    </div>
                </div>
                <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                    <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                    <span class="ml-2 my-auto hind">
                        {{ $t('Upload your logo in .svg, .png, .gif, or .jpg format. The logo will be used on the login page and throughout email communications.') }}
                    </span>
                </div>
            </div>
            <div v-if="form.errors?.bigLogo" class="mt-1 text-xs text-red-500">
                {{ $t(form.errors?.bigLogo) }}
            </div>
            <label class="block mt-12 mb-4 xsDark">
                {{ $t('Small Logo (Upload by click or drag & drop') }}
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
                        {{ $t('Drag your small artwork logo here') }}
                    </div>
                    <div class="cursor-pointer" v-else-if="!smallLogoPreview">
                        <img :src="$page.props.small_logo" alt="Logo" class="rounded-md h-40 w-40">
                    </div>
                </div>

                <div v-if="this.$page.props.show_hints" class="col-span-4 items-center flex">
                    <SvgCollection svgName="arrowLeft" class="ml-2 -mt-4"/>
                    <span class="hind ml-2 my-auto">
                        {{ $t('Upload your logo in .svg, .png, .gif, or .jpg format. The logo will be used in the header of your artwork.') }}
                    </span>
                </div>

            </div>
            <div v-if="form.errors?.smallLogo" class="mt-1 text-xs text-red-500">
                {{ $t(form.errors?.smallLogo) }}
            </div>
            <label class="block mt-12 mb-4 xsDark">
                {{ $t('Login Illustration (Upload by click or drag & drop)') }}
            </label>
            <div class="grid grid-cols-6 gap-x-12 items-center">
                <div
                    class="flex col-span-2 w-full justify-center border-2 bg-stone-50 w-80 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                    @click="selectNewBanner"
                    @dragover.prevent
                    @drop.stop.prevent="uploadDraggedBanner($event)">
                    <div v-show="!bannerPreview" class="space-y-1 text-center">
                        <div class="xsLight flex my-auto h-40 items-center"
                             v-if="$page.props.banner === null && bannerPreview === null">
                            {{ $t('Drag your login illustration here') }}
                            <input id="banner-upload"
                                   ref="banner"
                                   @change="updateBannerPreview"
                                   name="file-upload"
                                   type="file"
                                   class="sr-only"
                            />
                        </div>
                        <div class="cursor-pointer" v-else>
                            <img :src="$page.props.banner" :alt="$t('Current banner')" class="rounded-md h-40 w-40">
                        </div>
                    </div>
                    <div class="cursor-pointer">
                        <img v-show="bannerPreview"
                             :src="bannerPreview"
                             :alt="$t('Current banner')"
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
                        {{ $t('Upload your illustration in .svg, .png, .gif, or .jpg format. The illustration will be used on the login page.') }}
                    </span>
                </div>
            </div>
            <div v-if="form.errors?.banner" class="mt-1 text-xs text-red-500">
                {{ $t(form.errors?.banner) }}
            </div>
        </form>

    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    components: {
        FormButton,
        JetInputError,
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
              "image/gif",
              "image/webp",
            ]


            if (allowedTypes.includes(file?.type)) {
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
                this.uploadDocumentFeedback = this.$t(
                    'Only logos and illustrations of the type .jpeg, .svg, .png, .webp and .gif are accepted.'
                );
            }

            setTimeout(() => {
                this.changeLogos();
            }, 100);
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
