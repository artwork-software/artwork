<template>
    <BaseModal @closed="this.close()" v-if="true" modal-image="/Svgs/Overlays/illu_project_new.svg">
            <div class="mx-4">
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3">
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
import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: 'ProjectExportBudgetsByBudgetDeadlineModal',
    components: {
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
            showMandatoryFieldsErrorText: false
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
                            endBudgetDeadline: this.endBudgetDeadline
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
