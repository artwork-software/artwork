<template>
    <ToolSettingsHeader>
        <h2 class="headline2">{{ $t('Sage interface') }}</h2>
        <div class="grid grid-cols-12">
            <div class="xsLight mt-4 col-span-9">
                {{ $t('Would you like to have the bookings from Sage automatically imported into the respective project budgets? Then simply enter your API key here and say when the daily API query should take place.') }}
            </div>
            <div class="flex items-center justify-end col-span-3">
                <div class="flex items-center mr-2">
                    <span class="ml-1 my-auto hind">{{ $t('Execute data retrieval from Sage again') }}&nbsp;</span>
                    <SvgCollection svgName="smallArrowRight" class="h-6 w-6 ml-2 mr-2"/>
                </div>
                <RefreshIcon :class="[
                                !this.sageInterfaceIsConfigured() || this.importProcessing ?
                                    'bg-gray-600 cursor-not-allowed' :
                                    'bg-buttonBlue cursor-pointer',
                                'w-10 h-10 rounded-full text-white p-2'
                             ]"
                             @click="this.initializeSageImport()"
                />
            </div>
        </div>
        <div class="w-1/2 mt-4 grid grid-cols-1 gap-3">
            <input-component v-model="this.sageForm.host" :placeholder="$t('Host')"/>
            <div class="errorText" v-if="showHostErrorText">{{ $t('The host must be specified.') }}</div>
            <input-component v-model="this.sageForm.endpoint" :placeholder="$t('Endpoint')"/>
            <div class="errorText" v-if="showEndpointErrorText">{{ $t('The end point must be specified.') }}</div>
            <input-component v-model="this.sageForm.user" :placeholder="$t('User')"/>
            <div class="errorText" v-if="showUserErrorText">{{ $t('The user must be specified.') }}</div>
            <input type="password"
                   v-model="this.sageForm.password"
                   :placeholder="$t('Password')"
                   class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
            />
            <div class="errorText" v-if="showPasswordErrorText">{{ $t('The password must be entered.') }}</div>
            <div class="flex flex-col xsLight gap-3">
                <div class="flex items-center justify-end w-full">
                    <div class="group relative">
                        <InformationCircleIcon class="w-5 h-5 mr-1"/>
                        <div class="hidden group-hover:flex absolute z-10 top-5 left-5 w-96 h-auto bg-gray-600 text-white p-2">
                            {{ $t('Is automatically adjusted to the last posting date of the data already imported after the data has been imported.') }}
                        </div>
                    </div>
                    <span>{{ $t('Query data from this booking date') }}&nbsp;</span>
                    <input v-model="this.sageForm.bookingDate"
                           type="date"
                           class="text-center border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none"
                    />
                </div>
                <div class="flex items-center justify-end">
                    <span>{{ $t('Query daily at') }}&nbsp;</span>
                    <input v-model="this.sageForm.fetchTime"
                           type="time"
                           style="width:82px;"
                           class="text-center border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none"
                    />
                </div>
                <div class="flex items-center justify-end">
                    <label for="sageEnabled">{{ $t('Interface enabled') }}&nbsp;</label>
                    <input type="checkbox"
                           id="sageEnabled"
                           class="checkBoxOnDark"
                           v-model="this.sageForm.enabled"
                    />
                </div>
            </div>
            <FormButton  classes="text-center justify-center" :text="$t('Save interface settings')" @click="this.showConfirmationComponent = true;" />

        </div>
        <div class="flex flex-col space-y-4">
            <hr class="mt-5"/>
            <h2 class="headline2">{{ $t('Import a specific booking date') }}</h2>
            <div class="xsLight mt-4 col-span-9">
                {{ $t('Import individual booking days again. Existing data is overwritten with new data.') }}
            </div>
            <div v-if="!this.sageInterfaceIsConfigured()" class="errorText">{{ $t('Please configure the Sage interface first.') }}</div>
            <div class="flex flex-row items-center space-x-4">
                <input type="date"
                       v-model="this.specificDayImportDate"
                       :disabled="!this.sageInterfaceIsConfigured()"
                       :class="[
                                !this.sageInterfaceIsConfigured() ?
                                    'cursor-not-allowed' :
                                    'cursor-pointer',
                                ''
                             ]"
                />
                <RefreshIcon :class="[
                                !this.sageInterfaceIsConfigured() ||
                                this.importProcessing ||
                                this.specificDayImportDate === null || this.specificDayImportDate === '' ?
                                    'bg-gray-600 cursor-not-allowed' :
                                    'bg-buttonBlue cursor-pointer',
                                'w-10 h-10 rounded-full text-white p-2'
                             ]"
                             @click="this.initializeSageImportForSpecificDay()"
                />
            </div>
        </div>
    </ToolSettingsHeader>
    <confirmation-component v-if="this.showConfirmationComponent"
                            :titel="$t('Interface changes')"
                            :confirm="$t('Apply changes')"
                            :description="$t('Are you sure you want to change the interface settings')"
                            @closed="this.saveSageInterface"
    />
    <success-modal v-if="this.$page.props.flash.success"
                   :title="$t('Sage interface')"
                   :description="this.$page.props.flash.success"
                   :button="$t('Close message')"
                   @closed="this.$page.props.flash.success = null;"
    />
    <error-component v-if="this.$page.props.flash.error"
                     :titel="$t('An error has occurred')"
                     :description="this.$page.props.flash.error"
                     :confirm="$t('Close message')"
                     @closed="this.$page.props.flash.error = null;"
    />
</template>

<script>
import {defineComponent} from "vue";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import Input from "@/Jetstream/Input.vue";
import {RefreshIcon, InformationCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    components: {
        FormButton,
        ErrorComponent,
        SuccessModal,
        ConfirmationComponent,
        SvgCollection,
        Input,
        AppLayout,
        ToolSettingsHeader,
        InputComponent,
        RefreshIcon,
        InformationCircleIcon
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
            sageForm: useForm({
                host: this.sageSettings ? this.sageSettings.host : null,
                endpoint: this.sageSettings ? this.sageSettings.endpoint : null,
                user: this.sageSettings ? this.sageSettings.user : null,
                password: this.sageSettings ? this.sageSettings.password : null,
                bookingDate: this.sageSettings ? this.sageSettings.bookingDate : null,
                fetchTime: this.sageSettings ? this.sageSettings.fetchTime : null,
                enabled: this.sageSettings ? this.sageSettings.enabled : false
            }),
            importProcessing: false,
            specificDayImportDate: null,
        }
    },
    methods: {
        sageInterfaceIsConfigured() {
            return typeof this.sageSettings?.host !== 'undefined' &&
                typeof this.sageSettings?.endpoint !== 'undefined' &&
                typeof this.sageSettings?.user !== 'undefined' &&
                typeof this.sageSettings?.password !== 'undefined';
        },
        initializeSageImport() {
            if (!this.sageInterfaceIsConfigured() || this.importProcessing) {
                return;
            }

            this.importProcessing = true;
            this.$inertia.post(
                route('tool.interfaces.sage.initialize'),
                {},
                {
                    preserveScroll: true,
                    preserveState: false,
                    onFinish() {
                        this.importProcessing = false;
                    }
                }
            );
        },
        initializeSageImportForSpecificDay() {
            if (
                !this.sageInterfaceIsConfigured() ||
                this.importProcessing ||
                this.specificDayImportDate === null ||
                this.specificDayImportDate === ''
            ) {
                return;
            }

            this.importProcessing = true;
            this.$inertia.post(
                route('tool.interfaces.sage.initializeSpecificDay'),
                {
                    specificDay: this.specificDayImportDate
                },
                {
                    preserveScroll: true,
                    preserveState: false,
                    onFinish() {
                        this.importProcessing = false;
                    }
                }
            );
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

            this.sageForm.post(
                route('tool.interfaces.sage.update'),
                {
                    preserveScroll: true,
                    preserveState: false
                }
            );
        }
    }
});
</script>
