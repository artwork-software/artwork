<template>
    <ToolSettingsHeader :title="$t('Sage interface')">
        <h2 class="headline2">{{ $t('Sage interface') }}</h2>
        <div class="grid grid-cols-12">
            <div class="xsLight mt-4 col-span-9">
                {{ $t('Would you like to have the bookings from Sage automatically imported into the respective project budgets? Then simply enter your API key here and say when the daily API query should take place.') }}
            </div>
            <div class="flex items-center justify-end col-span-3">
                <div class="flex items-center mr-2">
                    <span class="ml-1 my-auto hind">{{ $t('Execute data retrieval from Sage again') }}&nbsp;</span>
                    <component is="IconArrowCurveRight" class="h-6 w-6 ml-1 mr-1 rotate-90 hind" stroke-width="1.7"/>
                </div>
                <div class="flex flex-row gap-1">
                    <RefreshIcon :class="[
                                    !this.sageInterfaceIsConfigured() || this.importProcessing ?
                                        'bg-gray-600 cursor-not-allowed' :
                                        'bg-artwork-buttons-create cursor-pointer',
                                    'w-10 h-10 rounded-full text-white p-2'
                                 ]"
                                 @click="this.initializeSageImport()"
                    />
                    <TrashIcon :class="[
                                    !this.sageInterfaceIsConfigured() || this.importProcessing ?
                                        'bg-gray-600 cursor-not-allowed' :
                                        'bg-artwork-buttons-create cursor-pointer',
                                    'w-10 h-10 rounded-full text-white p-2'
                                 ]"
                               @click="this.deleteSageData()"/>
                </div>
            </div>
        </div>
        <div class="w-1/2 mt-4 grid grid-cols-1 gap-4">
            <BaseInput v-model="this.sageForm.host" :label="$t('Host')" id="host" />
            <div class="text-red-500 text-xs mt-1" v-if="showHostErrorText">{{ $t('The host must be specified.') }}</div>
            <BaseInput v-model="this.sageForm.endpoint" id="endpoint" :label="$t('Endpoint')"/>
            <div class="text-red-500 text-xs mt-1" v-if="showEndpointErrorText">{{ $t('The end point must be specified.') }}</div>
            <BaseInput v-model="this.sageForm.user" id="user" :label="$t('User')"/>
            <div class="text-red-500 text-xs mt-1" v-if="showUserErrorText">{{ $t('The user must be specified.') }}</div>
            <BaseInput type="password"
                   v-model="this.sageForm.password"
                   :label="$t('Password')"
                   id="password"
            />
            <div class="errorText" v-if="showPasswordErrorText">{{ $t('The password must be entered.') }}</div>
            <div class="grid grid-cols-1 gap-4 xsLight">
                <div class="flex items-center justify-end w-full h-full">
                    <div class="group relative">
                        <InformationCircleIcon class="w-5 h-5 mr-1"/>
                        <div class="hidden group-hover:flex absolute z-10 top-5 left-5 w-96 h-auto bg-gray-600 text-white p-2">
                            {{ $t('Is automatically adjusted to the last posting date of the data already imported after the data has been imported.') }}
                        </div>
                    </div>
                    <span>{{ $t('Query data from this booking date') }}&nbsp;</span>
                    <div class="w-72 ml-2">
                        <BaseInput type="date"
                            v-model="this.sageForm.bookingDate"
                            label="tt.mm.yyyy"
                            id="bookingDate"
                        />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-x-3">
                    <span>{{ $t('Query daily at') }}&nbsp;</span>
                    <div class="w-28">
                        <BaseInput type="time"
                                   v-model="this.sageForm.fetchTime"
                                   label="hh:mm"
                                   id=""
                        />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-x-3">
                    <label for="sageEnabled">{{ $t('Interface enabled') }}&nbsp;</label>
                    <input type="checkbox"
                           id="sageEnabled"
                           class="input-checklist"
                           v-model="this.sageForm.enabled"
                    />
                </div>
            </div>
            <FormButton  classes="text-center justify-center" :text="$t('Save interface settings')" @click="this.showConfirmationComponent = true;" />
        </div>
        <div class="flex flex-col space-y-4">
            <hr class="mt-5"/>
            <h2 class="headline2">{{ $t('Import a specific booking date') }}</h2>
            <div class="xsLight col-span-9">
                {{ $t('Import individual booking days again. Existing data is overwritten with new data.') }}
            </div>
            <div v-if="!this.sageInterfaceIsConfigured()" class="errorText">{{ $t('Please configure the Sage interface first.') }}</div>
            <div class="flex flex-row items-center space-x-4">
                <div class="w-96">
                    <BaseInput type="date"
                        label="tt.mm.yyyy"
                        id="specificDayImportDate"
                        v-model="this.specificDayImportDate"
                        :disabled="!this.sageInterfaceIsConfigured()"
                        :class="[!this.sageInterfaceIsConfigured() ? 'cursor-not-allowed' : 'cursor-pointer', '']"
                    />
                </div>
                <RefreshIcon :class="[
                                !this.sageInterfaceIsConfigured() ||
                                this.importProcessing ||
                                this.specificDayImportDate === null || this.specificDayImportDate === '' ?
                                    'bg-gray-600 cursor-not-allowed' :
                                    'bg-artwork-buttons-create cursor-pointer',
                                'w-10 h-10 rounded-full text-white p-2'
                             ]"
                             @click="this.initializeSageImportForSpecificDay()"
                />
            </div>
        </div>
        <hr class="my-5"/>
        <div class="flex flex-col">
            <div class="headline2">
                {{ $t("Column Order") }}
            </div>
            <div class="text-sm mb-5">
                {{ $t("Configure the order of the first two columns in the budget. This sorting is only used when displaying columns. The first column is always considered the 'Debit account' and the second the 'Cost center'.") }}
            </div>
            <draggable class="flex flex-col gap-2"
                       ghost-class="opacity-50"
                       key="draggableKey"
                       item-key="id"
                       :list="tableColumnOrder"
                       @start="dragging=true"
                       @end="dragging=false"
                       @change="updateTableColumnOrders()">
                <template #item="{element}" :key="element.id">
                    <div class="text-sm flex flex-row gap-x-1 w-full p-4 group bg-artwork-project-background rounded-lg cursor-grab"
                         :key="element.id"
                         :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                        <IconDragDrop class="h-5 w-5 hidden group-hover:block"/>
                        <span class="ml-2 group-hover:font-bold">
                            {{ $t(element.display_text, [element.position === 0 ? $t('Debit account') : $t('Cost center')]) }}
                        </span>
                    </div>
                </template>
            </draggable>
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
import {router, useForm} from "@inertiajs/vue3";
import Input from "@/Jetstream/Input.vue";
import {InformationCircleIcon, RefreshIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import {IconDragDrop} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    components: {
        BaseInput,
        IconDragDrop,
        TimeInputComponent,
        DateInputComponent,
        TextInputComponent,
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
        InformationCircleIcon,
        TrashIcon,
        draggable
    },
    props: [
        'sageSettings',
        'tableColumnOrder'
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
            dragging: false
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
        },
        deleteSageData() {
            router.delete(
                route('tool.interfaces.sage.delete'),
                {
                    preserveState: true,
                    preserveScroll: true
                }
            );
        },
        updateTableColumnOrders() {
            router.patch(
                route('project.budget.updateTableColumnOrders'),
                {
                    tableColumnOrders: this.tableColumnOrder.map(
                        (tableColumnOrder) => {
                            //indices of payload are the new positions
                            return tableColumnOrder.id;
                        }
                    )
                },
                {
                    preserveState: true,
                    preserveScroll: true
                }
            );
        }
    }
});
</script>
