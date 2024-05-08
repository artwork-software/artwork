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
                <div class="mt-4 w-full flex flex-row">
                    <div class="w-1/2">
                        <label for="startDate" class="xxsLight">
                            {{ $t('Start date') }}
                        </label>
                        <input v-model="startBudgetDeadline" type="date" class="w-full"/>
                    </div>
                    <div class="w-1/2">
                        <label for="endDate" class="xxsLight">
                            {{ $t('End date') }}
                        </label>
                        <input v-model="endBudgetDeadline" type="date" class="w-full"/>
                    </div>
                </div>
                <div v-if="showMandatoryFieldsErrorText" class="w-full text-center mt-3">
                    <span class="text-red-600 text-xs">
                        {{ $t('You must specify both the start and end date. Then start the export again.') }}
                    </span>
                </div>
                <div class="mt-5 mb-3 w-full grid justify-items-center">
                    <button @click="downloadExportProjectBudgetsByBudgetDeadline()"
                            type="button"
                            class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-artwork-buttons-create focus:outline-none bg-artwork-buttons-create hover:bg-artwork-buttons-hover">
                        <DocumentReportIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                        <p class="text-sm">{{ $t('Export') }}</p>
                    </button>
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

export default {
    name: 'ProjectExportBudgetsByBudgetDeadlineModal',
    components: {
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
