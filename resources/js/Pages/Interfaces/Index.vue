<template>
    <ToolSettingsHeader>
        <h2 class="headline2">Schnittstelle Sage</h2>
        <div class="grid grid-cols-12">
            <div class="xsLight mt-4 col-span-9">
                Du möchtest die Buchungen aus Sage automatisch in die jeweiligen Projektbudgets einlesen lass? Dann gib'
                hier einfach deinen API-Key ein und sage wann die tägliche API-Abfrage erfolgen soll.
            </div>
            <div class="flex items-center justify-end col-span-3">
                <div class="flex items-center mr-2">
                    <span class="ml-1 my-auto hind">Datenabruf aus Sage erneut ausführen&nbsp;</span>
                    <SvgCollection svgName="smallArrowRight" class="h-6 w-6 ml-2 mr-2"/>
                </div>
                <RefreshIcon class="cursor-pointer w-10 h-10 bg-buttonBlue rounded-full text-white p-2"
                             @click="initializeSageImport"
                />
            </div>
        </div>
        <div class="w-1/2 mt-4 grid grid-cols-1 gap-3">
            <input-component v-model="this.sageForm.host" placeholder="Host"/>
            <div class="errorText" v-if="showHostErrorText">Der Host muss angegeben werden.</div>
            <input-component v-model="this.sageForm.endpoint" placeholder="Endpunkt"/>
            <div class="errorText" v-if="showEndpointErrorText">Der Endpunkt muss angegeben werden.</div>
            <input-component v-model="this.sageForm.user" placeholder="Benutzer"/>
            <div class="errorText" v-if="showUserErrorText">Der Benutzer muss angegeben werden.</div>
            <input type="password"
                   v-model="this.sageForm.password"
                   placeholder="Passwort"
                   class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
            />
            <div class="errorText" v-if="showPasswordErrorText">Das Passwort muss angegeben werden.</div>
            <div class="flex flex-col xsLight gap-3">
                <div class="flex items-center justify-end">
                    Abfrage täglich um&nbsp;
                    <input v-model="this.sageForm.fetchTime"
                           id="fetchTime"
                           type="time"
                           style="width:82px;"
                           class="text-center border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none"
                    />
                </div>
                <div class="flex items-center justify-end">
                    <label for="sageEnabled">Schnittstelle aktiv&nbsp;</label>
                    <input type="checkbox"
                           id="sageEnabled"
                           class="checkBoxOnDark"
                           v-model="this.sageForm.enabled"
                    />
                </div>
            </div>
            <input class="p-4 cursor-pointer text-sm text-white rounded-full bg-buttonBlue hover:bg-buttonHover"
                   type="button"
                   value="Schnittstelleneinstellungen speichern"
                   @click="this.showConfirmationComponent = true;"
            />
        </div>
    </ToolSettingsHeader>
    <confirmation-component v-if="this.showConfirmationComponent"
                            titel="Schnittstellenänderungen"
                            confirm="Änderungen anwenden"
                            description="Bist du sicher, dass du die Schnittstelleneinstellungen ändern möchtest"
                            @closed="this.saveSageInterface"
    />
    <success-modal v-if="this.$page.props.flash.success"
                   title="Schnittstellenänderungen"
                   :description="this.$page.props.flash.success"
                   button="Meldung schließen"
                   @closed="this.$page.props.flash.success = null;"
    />
    <error-component v-if="this.$page.props.flash.error"
                     title="Es ist ein Fehler aufgetreten"
                     :description="this.$page.props.flash.error"
                     confirm="Meldung schließen"
                     @closed="this.$page.props.flash.error = null;"
    />

    <SuccessModal v-if="showSuccessInitializeSageImport"
                  title="Sage Import"
                  description="Der Import wurde erfolgreich gestartet."
                  button="Meldung schließen"
                  @closed="showSuccessInitializeSageImport = false;"
    />
</template>

<script>
import {defineComponent} from "vue";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
import Input from "@/Jetstream/Input.vue";
import {RefreshIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

export default defineComponent({
    components: {
        ErrorComponent,
        SuccessModal,
        ConfirmationComponent,
        SvgCollection,
        Input,
        AddButton,
        AppLayout,
        ToolSettingsHeader,
        InputComponent,
        RefreshIcon
    },
    props: [
        'sageSettings'
    ],
    data() {
        return {
            showConfirmationComponent: false,
            showHostErrorText: false,
            showEndpointErrorText: false,
            showUserErrorText: false,
            showPasswordErrorText: false,
            showSuccessInitializeSageImport: false,
            sageForm: useForm({
                host: this.sageSettings ? this.sageSettings.host : null,
                endpoint: this.sageSettings ? this.sageSettings.endpoint : null,
                user: this.sageSettings ? this.sageSettings.user : null,
                password: this.sageSettings ? this.sageSettings.password : null,
                fetchTime: this.sageSettings ? this.sageSettings.fetchTime : null,
                enabled: this.sageSettings ? this.sageSettings.enabled : false
            })
        }
    },
    methods: {
        initializeSageImport() {
            this.$inertia.post(route('tool.interfaces.sage.initialize'), {

            }, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        saveSageInterface(closedToSave) {
            this.showConfirmationComponent = false;

            if (!closedToSave) {
                return;
            }

            this.showHostErrorText = this.sageForm.host === null || this.sageForm.host === '';
            this.showEndpointErrorText = this.sageForm.endpoint === null || this.sageForm.endpoint === '';
            this.showUserErrorText = this.sageForm.user === null || this.sageForm.user === '';
            this.showPasswordErrorText = this.sageForm.password === null || this.sageForm.password === '';

            if (
                this.showHostErrorText ||
                this.showEndpointErrorText ||
                this.showUserErrorText ||
                this.showPasswordErrorText
            ) {
                return;
            }

            this.sageForm.post(route('tool.interfaces.sage.update'));
        }
    }
});
</script>
