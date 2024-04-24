<template>
    <ToolSettingsHeader>
        <div v-if="this.$page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ this.$page.props.flash.success }}
        </div>
        <h2 class="headline2">{{ $t('Communication & Legal') }}</h2>
        <div class="xsLight mt-4">
            {{ $t("Define the footer text for all system emails here, and provide the links to your company's legal notice and privacy policy pages. Additionally, you can specify an email address to be used for sending emails.") }}
        </div>
        <div class="mt-4">
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-3">
                    <inputComponent @focusout="changeEmailData" v-model="mailForm.businessName" :placeholder="$t('Our Organization')"/>
                </div>
            </div>
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-3">
                    <inputComponent @focusout="changeEmailData" v-model="mailForm.impressumLink" :placeholder="$t('Link to Legal Notice')"/>
                    <span v-if="showInvalidImpressumLinkErrorText"
                          class="errorText">
                        {{ $t('Invalid URL (Example: https://google.com)') }}
                    </span>
                </div>
            </div>
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-3">
                    <inputComponent @focusout="changeEmailData" v-model="mailForm.privacyLink" :placeholder="$t('Link to Privacy Policy')"/>
                    <span v-if="showInvalidPrivacyLinkErrorText"
                          class="errorText">
                        {{ $t('Invalid URL (Example: https://google.com)') }}
                    </span>
                </div>
            </div>
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-3">
                    <inputComponent @focusout="changeEmailData" v-model="mailForm.businessEmail" :placeholder="$t('Business Email')"/>
                    <span v-if="showInvalidBusinessEmailAddressErrorText"
                          class="errorText">
                        {{ $t('Invalid Email Address') }}
                    </span>
                </div>
            </div>
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-8">
                    <textarea
                        :placeholder="$t('Email-Footer')"
                        v-model="mailForm.emailFooter"
                        @focusout="changeEmailData"
                        rows="4"
                        class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-2 w-full placeholder:xsLight border-2 border-gray-300 "/>
                </div>
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    components: {
        FormButton,
        ToolSettingsHeader,
        InputComponent
    },
    data() {
        return {
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
        changeEmailData() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            this.showInvalidBusinessEmailAddressErrorText =
                this.mailForm.businessEmail !== '' && !emailRegex.test(this.mailForm.businessEmail);

            const urlRegex = /^https:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(:[0-9]+)?(\/[^]*)?$/;
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

            if(this.mailForm.isDirty){
                this.mailForm.patch(route('tool.communication-and-legal.update'));
            }

        }
    }
})
</script>
