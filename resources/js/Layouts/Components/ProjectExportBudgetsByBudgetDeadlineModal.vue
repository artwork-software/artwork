<template>
    <BaseModal @closed="this.close()" v-if="true" modal-image="/Svgs/Overlays/illu_project_new.svg">
        <div class="mx-4 flex flex-col">
            <div>
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{ $t('Export project budgets') }}
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm xsLight">
                    {{ $t('All project budgets whose budget key date is between the following dates are exported.') }}
                </span>
            </div>
            <div class="flex flex-col mt-4 gap-y-2">
                <SwitchGroup as="div" class="flex items-center gap-x-2 -mb-1">
                    <SwitchLabel as="span" class="text-sm">
                        <span  :class="!generateDetailedExport ? 'text-black font-bold' : 'xsLight'">
                            {{ $t('Aggregated projects') }}
                        </span>
                    </SwitchLabel>
                    <Switch v-model="generateDetailedExport" :class="[generateDetailedExport ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                        <span aria-hidden="true" :class="[generateDetailedExport ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="generateDetailedExport ? 'text-black font-bold' : 'xsLight'">
                            {{ $t('Itemised projects') }}
                        </span>
                    </SwitchLabel>
                </SwitchGroup>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 -ml-1">
                    <div>
                        <DateInputComponent id="startDate" :label="$t('Start date')" v-model="startBudgetDeadline"/>
                    </div>
                    <div>
                        <DateInputComponent id="startDate" :label="$t('End date')" v-model="endBudgetDeadline"/>
                    </div>
                </div>
                <div v-if="showMandatoryFieldsErrorText" class="w-full text-center mt-3">
                    <span class="text-red-600 text-xs">
                        {{ $t('You must specify both the start and end date. Then start the export again.') }}
                    </span>
                </div>
            </div>
            <div class="mt-5 mb-3 w-full grid justify-items-center">
                <BaseButton @click="downloadExportProjectBudgetsByBudgetDeadline()" :text="$t('Export')">
                    <DocumentReportIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import Input from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, DocumentReportIcon, XIcon} from "@heroicons/vue/outline";
import {Disclosure, DisclosureButton, DisclosurePanel, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: 'ProjectExportBudgetsByBudgetDeadlineModal',
    components: {
        SwitchLabel,
        Switch,
        SwitchGroup,
        BaseButton,
        FormButton,
        DateInputComponent,
        BaseModal,
        DocumentReportIcon,
        DisclosurePanel,
        DisclosureButton,
        Disclosure,
        XIcon,
        ChevronDownIcon,
        CheckIcon,
        JetDialogModal,
        TagComponent,
        Button,
        Input
    },
    emits: ['closeProjectExportBudgetsByBudgetDeadlineModal'],
    data() {
        return {
            startBudgetDeadline: null,
            endBudgetDeadline: null,
            showMandatoryFieldsErrorText: false,
            generateDetailedExport: false
        }
    },
    methods: {
        downloadExportProjectBudgetsByBudgetDeadline() {
            if (this.startBudgetDeadline === null || this.endBudgetDeadline === null) {
                this.showMandatoryFieldsErrorText = true;
            } else {
                if (this.showMandatoryFieldsErrorText) {
                    this.showMandatoryFieldsErrorText = false;
                }

                window.open(
                    route(
                        'projects.export.budgetByBudgetDeadline',
                        {
                            startBudgetDeadline: this.startBudgetDeadline,
                            endBudgetDeadline: this.endBudgetDeadline,
                            type: this.generateDetailedExport
                        }
                    )
                );

                this.close();
            }
        },
        close() {
            this.$emit('closeProjectExportBudgetsByBudgetDeadlineModal');
        }
    }
}
</script>
