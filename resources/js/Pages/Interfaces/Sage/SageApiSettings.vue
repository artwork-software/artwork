<template>
    <div>
        <h2 class="headline2">{{ $t('Sage interface') }}</h2>
        <div class="grid grid-cols-12">
            <div class="xsLight mt-4 col-span-9">
                {{
                    $t('Would you like to have the bookings from Sage automatically imported into the respective project budgets? Then simply enter your API key here and say when the daily API query should take place.')
                }}
            </div>
            <div class="flex items-center justify-end col-span-3">
                <div class="flex items-center mr-2">
                    <span class="ml-1 my-auto hind">{{ $t('Execute data retrieval from Sage again') }}&nbsp;</span>
                    <component :is="IconArrowCurveRight" class="h-6 w-6 ml-1 mr-1 rotate-90 hind" stroke-width="1.7"/>
                </div>
                <div class="flex flex-row gap-1">
                    <RefreshIcon :class="[
                        !sageInterfaceIsConfigured() || importProcessing
                            ? 'bg-gray-600 cursor-not-allowed'
                            : 'bg-artwork-buttons-create cursor-pointer',
                        'w-10 h-10 rounded-full text-white p-2'
                    ]" @click="initializeSageImport()" />
                    <TrashIcon :class="[
                        !sageInterfaceIsConfigured() || importProcessing
                            ? 'bg-gray-600 cursor-not-allowed'
                            : 'bg-artwork-buttons-create cursor-pointer',
                        'w-10 h-10 rounded-full text-white p-2'
                    ]" @click="deleteSageData()" />
                </div>
            </div>
        </div>

        <div class="w-1/2 mt-4 grid grid-cols-1 gap-4">
            <BaseInput v-model="sageForm.host" :label="$t('Host')" id="host"/>
            <div class="text-red-500 text-xs mt-1" v-if="showHostErrorText">
                {{ $t('The host must be specified.') }}
            </div>
            <BaseInput v-model="sageForm.endpoint" id="endpoint" :label="$t('Endpoint')"/>
            <div class="text-red-500 text-xs mt-1" v-if="showEndpointErrorText">
                {{ $t('The end point must be specified.') }}
            </div>
            <BaseInput v-model="sageForm.user" id="user" :label="$t('User')"/>
            <div class="text-red-500 text-xs mt-1" v-if="showUserErrorText">
                {{ $t('The user must be specified.') }}
            </div>
            <BaseInput type="password" v-model="sageForm.password" :label="$t('Password')" id="password"/>
            <div class="errorText" v-if="showPasswordErrorText">
                {{ $t('The password must be entered.') }}
            </div>

            <div class="grid grid-cols-1 gap-4 xsLight">
                <div class="flex items-center justify-end w-full h-full">
                    <div class="group relative">
                        <InformationCircleIcon class="w-5 h-5 mr-1"/>
                        <div class="hidden group-hover:flex absolute z-10 top-5 left-5 w-96 h-auto bg-gray-600 text-white p-2">
                            {{
                                $t('Is automatically adjusted to the last posting date of the data already imported after the data has been imported.')
                            }}
                        </div>
                    </div>
                    <span>{{ $t('Query data from this booking date') }}&nbsp;</span>
                    <div class="w-72 ml-2">
                        <BaseInput type="date" v-model="sageForm.bookingDate" label="tt.mm.yyyy" id="bookingDate"/>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-x-3">
                    <span>{{ $t('Query daily at') }}&nbsp;</span>
                    <div class="w-28">
                        <BaseInput type="time" v-model="sageForm.fetchTime" label="hh:mm" id=""/>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-x-3">
                    <label for="sageEnabled">{{ $t('Interface enabled') }}&nbsp;</label>
                    <input type="checkbox" id="sageEnabled" class="input-checklist" v-model="sageForm.enabled"/>
                </div>
            </div>

            <FormButton class="text-center justify-center" :text="$t('Save interface settings')" @click="showConfirmationComponent = true;" />
        </div>

        <div class="flex flex-col space-y-4">
            <hr class="mt-5"/>
            <h2 class="headline2">{{ $t('Import a specific booking date') }}</h2>
            <div class="xsLight col-span-9">
                {{ $t('Import individual booking days again. Existing data is overwritten with new data.') }}
            </div>
            <div v-if="!sageInterfaceIsConfigured()" class="errorText">
                {{ $t('Please configure the Sage interface first.') }}
            </div>
            <div class="flex flex-row items-center space-x-4 flex-wrap gap-y-2">
                <div class="flex flex-row items-center gap-x-2">
                    <span class="xsLight">{{ $t('From') }}</span>
                    <div class="w-48">
                        <BaseInput type="date" label="tt.mm.yyyy" id="specificDayImportDateFrom"
                                   v-model="specificDayImportDateFrom"
                                   :disabled="!sageInterfaceIsConfigured()"
                                   :class="[!sageInterfaceIsConfigured() ? 'cursor-not-allowed' : 'cursor-pointer']"/>
                    </div>
                </div>
                <div class="flex flex-row items-center gap-x-2">
                    <span class="xsLight">{{ $t('Until') }}</span>
                    <div class="w-48">
                        <BaseInput type="date" label="tt.mm.yyyy" id="specificDayImportDateTo"
                                   v-model="specificDayImportDateTo"
                                   :disabled="!sageInterfaceIsConfigured()"
                                   :class="[!sageInterfaceIsConfigured() ? 'cursor-not-allowed' : 'cursor-pointer']"/>
                    </div>
                </div>
                <RefreshIcon :class="[
                    !sageInterfaceIsConfigured() ||
                    importProcessing ||
                    specificDayImportDateFrom === null || specificDayImportDateFrom === ''
                        ? 'bg-gray-600 cursor-not-allowed'
                        : 'bg-artwork-buttons-create cursor-pointer',
                    'w-10 h-10 rounded-full text-white p-2'
                ]" @click="initializeSageImportForSpecificDay()" />
            </div>
        </div>

        <hr class="my-5"/>

        <div class="flex flex-col">
            <div class="headline2">
                {{ $t('Column Order') }}
            </div>
            <div class="text-sm mb-5">
                {{
                    $t("Configure the order of the first two columns in the budget. This sorting is only used when displaying columns. The first column is always considered the 'Debit account' and the second the 'Cost center'.")
                }}
            </div>
            <draggable class="flex flex-col gap-2" ghost-class="opacity-50" key="draggableKey" item-key="id"
                       :list="tableColumnOrder" @start="dragging=true" @end="dragging=false"
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

        <confirmation-component v-if="showConfirmationComponent"
                                :titel="$t('Interface changes')"
                                :confirm="$t('Apply changes')"
                                :description="$t('Are you sure you want to change the interface settings')"
                                @closed="saveSageInterface" />
        <success-modal v-if="$page.props.flash.success"
                       :title="$t('Sage interface')"
                       :description="$page.props.flash.success"
                       :button="$t('Close message')"
                       @closed="$page.props.flash.success = null;" />
        <error-component v-if="$page.props.flash.error"
                         :titel="$t('An error has occurred')"
                         :description="$page.props.flash.error"
                         :confirm="$t('Close message')"
                         @closed="$page.props.flash.error = null;" />
    </div>
</template>

<script>
import { defineComponent } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { InformationCircleIcon, RefreshIcon, TrashIcon } from "@heroicons/vue/solid";
import {IconArrowCurveRight, IconDragDrop} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

export default defineComponent({
    components: {
        BaseInput,
        FormButton,
        ConfirmationComponent,
        SuccessModal,
        ErrorComponent,
        InformationCircleIcon,
        RefreshIcon,
        TrashIcon,
        IconDragDrop,
        draggable
    },
    props: ['sageSettings', 'tableColumnOrder'],
    data() {
        return {
            showConfirmationComponent: false,
            showHostErrorText: false,
            showEndpointErrorText: false,
            showUserErrorText: false,
            showPasswordErrorText: false,
            sageForm: useForm({
                host: this.sageSettings?.host ?? null,
                endpoint: this.sageSettings?.endpoint ?? null,
                user: this.sageSettings?.user ?? null,
                password: this.sageSettings?.password ?? null,
                bookingDate: this.sageSettings?.bookingDate ?? null,
                fetchTime: this.sageSettings?.fetchTime ?? null,
                enabled: this.sageSettings?.enabled ?? false
            }),
            importProcessing: false,
            specificDayImportDateFrom: null,
            specificDayImportDateTo: null,
            dragging: false
        }
    },
    methods: {
        IconArrowCurveRight,
        sageInterfaceIsConfigured() {
            return this.sageSettings?.host &&
                this.sageSettings?.endpoint &&
                this.sageSettings?.user &&
                this.sageSettings?.password;
        },
        initializeSageImport() {
            if (!this.sageInterfaceIsConfigured() || this.importProcessing) return;
            this.importProcessing = true;
            this.$inertia.post(route('tool.interfaces.sage.initialize'), {}, {
                preserveScroll: true,
                preserveState: false,
                onFinish: () => this.importProcessing = false
            });
        },
        initializeSageImportForSpecificDay() {
            if (!this.sageInterfaceIsConfigured() || this.importProcessing || !this.specificDayImportDateFrom) return;
            this.importProcessing = true;
            const specificDayTo = this.specificDayImportDateTo || this.specificDayImportDateFrom;
            this.$inertia.post(route('tool.interfaces.sage.initializeSpecificDay'), {
                specificDayFrom: this.specificDayImportDateFrom,
                specificDayTo: specificDayTo
            }, {
                preserveScroll: true,
                preserveState: false,
                onFinish: () => this.importProcessing = false
            });
        },
        saveSageInterface(closedToSave) {
            this.showConfirmationComponent = false;
            if (!closedToSave) return;

            this.showHostErrorText = !this.sageForm.host;
            this.showEndpointErrorText = !this.sageForm.endpoint;
            this.showUserErrorText = !this.sageForm.user;
            this.showPasswordErrorText = !this.sageForm.password;

            if (this.showHostErrorText || this.showEndpointErrorText || this.showUserErrorText || this.showPasswordErrorText) {
                return;
            }

            this.sageForm.post(route('tool.interfaces.sage.update'), {
                preserveScroll: true,
                preserveState: false
            });
        },
        deleteSageData() {
            router.delete(route('tool.interfaces.sage.delete'), {
                preserveState: true,
                preserveScroll: true
            });
        },
        updateTableColumnOrders() {
            router.patch(route('project.budget.updateTableColumnOrders'), {
                tableColumnOrders: this.tableColumnOrder.map(col => col.id)
            }, {
                preserveState: true,
                preserveScroll: true
            });
        }
    }
});
</script>
