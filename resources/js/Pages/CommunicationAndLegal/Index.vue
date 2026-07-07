<template>
    <ToolSettingsHeader :title="$t('Communication & Legal')">
        <div v-if="this.$page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ this.$page.props.flash.success }}
        </div>
        <div class="grid grid-cols-1 gap-4 mt-10 max-w-lg">
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.page_title" id="page_title" :label="$t('Page Title')"/>
                </div>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.businessName" id="businessName" :label="$t('Our Organization')"/>
                </div>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.impressumLink" id="impressumLink" :label="$t('Link to Legal Notice')"/>
                    <span v-if="showInvalidImpressumLinkErrorText"
                          class="text-red-500 text-xs mt-1">
                        {{ $t('Invalid URL (Example: https://google.com)') }}
                    </span>
                </div>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.privacyLink" id="privacyLink" :label="$t('Link to Privacy Policy')"/>
                    <span v-if="showInvalidPrivacyLinkErrorText"
                          class="text-red-500 text-xs mt-1">
                        {{ $t('Invalid URL (Example: https://google.com)') }}
                    </span>
                </div>
            </div>
            <div>
                <div class="sm:col-span-3">
                    <BaseInput id="invitationEmail"
                                        v-model="mailForm.invitationEmail"
                                        :label="$t('Invitation Email')"
                                        @focusout="updateCommunicationAndLegal"/>
                    <span v-if="showInvalidInvitationEmailAdressErrorText"
                          class="text-red-500 text-xs mt-1">
                        {{ $t('Invalid Email Address') }}
                    </span>
                </div>
            </div>
            <div>
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.businessEmail" id="businessEmail" :label="$t('Business Email')"/>
                    <span v-if="showInvalidBusinessEmailAddressErrorText"
                          class="text-red-500 text-xs mt-1">
                        {{ $t('Invalid Email Address') }}
                    </span>
                </div>
            </div>
            <div>
                <div class="sm:col-span-8">
                    <BaseInput
                        :label="$t('Email-Footer')"
                        v-model="mailForm.emailFooter"
                        @focusout="updateCommunicationAndLegal"
                        rows="4"
                        id="emailFooter"/>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="">
                    <BaseInput
                        :label="$t('Playing time window start')"
                        v-model="mailForm.playingTimeWindowStart"
                        @focusout="updateCommunicationAndLegal"
                        rows="4"
                        type="date"
                        id="playingTimeWindowStart"/>
                </div>
                <div class="">
                    <BaseInput
                        :label="$t('Playing time window end')"
                        v-model="mailForm.playingTimeWindowEnd"
                        @focusout="updateCommunicationAndLegal"
                        rows="4"
                        type="date"
                        id="playingTimeWindowEnd"/>
                </div>

            </div>

            <div class="mt-8 border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('Letterhead') }}</h3>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.letterheadName" id="letterheadName" :label="$t('Name in letterhead')"/>
                </div>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.letterheadStreet" id="letterheadStreet" :label="$t('Street for letterhead + house number')"/>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.letterheadZipCode" id="letterheadZipCode" :label="$t('Zip code for letterhead')"/>
                </div>
                <div class="">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.letterheadCity" id="letterheadCity" :label="$t('City for letterhead')"/>
                </div>
            </div>
            <div class="">
                <div class="sm:col-span-3">
                    <BaseInput @focusout="updateCommunicationAndLegal" v-model="mailForm.letterheadEmail" id="letterheadEmail" :label="$t('Email for letterhead')"/>
                </div>
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    components: {
        BaseInput,
        TextareaComponent,
        TextInputComponent,
        FormButton,
        ToolSettingsHeader,
        InputComponent
    },
    data() {
        return {
            mailForm: useForm({
                businessName: this.$page.props.businessName,
                page_title: this.$page.props.page_title,
                impressumLink: this.$page.props.impressumLink,
                privacyLink: this.$page.props.privacyLink,
                emailFooter: this.$page.props.emailFooter,
                invitationEmail: this.$page.props.invitationEmail,
                businessEmail: this.$page.props.businessEmail,
                playingTimeWindowStart: this.$page.props.playingTimeWindowStart,
                playingTimeWindowEnd: this.$page.props.playingTimeWindowEnd,
                letterheadName: this.$page.props.letterheadName,
                letterheadStreet: this.$page.props.letterheadStreet,
                letterheadZipCode: this.$page.props.letterheadZipCode,
                letterheadCity: this.$page.props.letterheadCity,
                letterheadEmail: this.$page.props.letterheadEmail,
            }),
            showInvalidInvitationEmailAdressErrorText: false,
            showInvalidBusinessEmailAddressErrorText: false,
            showInvalidImpressumLinkErrorText: false,
            showInvalidPrivacyLinkErrorText: false
        }
    },
    methods: {
        updateCommunicationAndLegal() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                urlRegex = /^https:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(:[0-9]+)?(\/[^]*)?$/;

            this.showInvalidBusinessEmailAddressErrorText =
                this.mailForm.businessEmail !== '' && !emailRegex.test(this.mailForm.businessEmail);
            this.showInvalidInvitationEmailAdressErrorText =
                this.mailForm.invitationEmail !== '' && !emailRegex.test(this.mailForm.invitationEmail);

            this.showInvalidImpressumLinkErrorText =
                this.mailForm.impressumLink !== '' && !urlRegex.test(this.mailForm.impressumLink);
            this.showInvalidPrivacyLinkErrorText =
                this.mailForm.privacyLink !== '' && !urlRegex.test(this.mailForm.privacyLink);

            if (
                this.showInvalidInvitationEmailAdressErrorText ||
                this.showInvalidBusinessEmailAddressErrorText ||
                this.showInvalidImpressumLinkErrorText ||
                this.showInvalidPrivacyLinkErrorText
            ) {
                return;
            }

            if (this.mailForm.isDirty) {
                this.mailForm.patch(
                    route('tool.communication-and-legal.update'),
                    {
                        preserveScroll: true
                    }
                );
            }

        }
    }
})
</script>
