<template>
    <ToolSettingsHeader>
        <div v-if="this.$page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ this.$page.props.flash.success }}
        </div>
        <h2 class="headline2">Kommunikation & Rechtliches</h2>
        <div class="xsLight mt-4">
            Definiere hier den Footer-Text für sämtliche System-E-Mails und gib' die Links zur
            Impressum- und Datenschutzseite deines Unternehmens an. Darüber hinaus kannst du eine
            E-Mail Adresse definieren die beim versenden von E-Mails verwendet wird.
        </div>
        <div class="mt-4">
            <div class="mt-4 col-span-9 grid grid-cols-9">
                <div class="sm:col-span-3">
                    <inputComponent v-model="mailForm.businessName" placeholder="Unsere Organisation"/>
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
                    <inputComponent v-model="mailForm.businessEmail" placeholder="Geschäfts-E-Mail-Adresse"/>
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
        <div class="mt-4 items-center">
            <AddButton @click.prevent="changeEmailData" text="Änderungen speichern" mode="modal"/>
        </div>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";

export default defineComponent({
    components: {
        ToolSettingsHeader,
        AddButton,
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

            this.mailForm.patch(route('tool.communication-and-legal.update'));
        }
    }
})
</script>
