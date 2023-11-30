<template>
    <jet-dialog-modal @close="this.close()">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_new.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div>
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Projekt Budgets exportieren
                    </div>
                    <XIcon @click="this.close()"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                </div>
                <div class="mt-4">
                    <span class="text-sm">Alle Projekt Budgets, deren Budget-Stichtag sich zwischen den folgenden Datumsangaben befindet, werden exportiert.</span>
                </div>
                <div class="mt-4 w-full flex flex-row">
                    <div class="w-1/2">
                        <label for="startDate" class="xxsLight">Startdatum</label>
                        <input v-model="startBudgetDeadline" type="date" class="w-full"/>
                    </div>
                    <div class="w-1/2">
                        <label for="endDate" class="xxsLight">Enddatum</label>
                        <input v-model="endBudgetDeadline" type="date" class="w-full"/>
                    </div>
                </div>
                <div v-if="showMandatoryFieldsErrorText" class="w-full text-center mt-3">
                    <span class="text-red-600 text-xs">
                        Sie müssen sowohl Start- als auch Enddatum angeben. Starten Sie den Export dann erneut.
                    </span>
                </div>
                <div class="mt-5 mb-3 w-full grid justify-items-center">
                    <button @click="downloadExportProjectBudgetsByBudgetDeadline()"
                            type="button"
                            class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton focus:outline-none bg-buttonBlue hover:bg-buttonHover">
                        <DocumentReportIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                        <p class="text-sm">Exportieren</p>
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import Input from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, DocumentReportIcon, XIcon} from "@heroicons/vue/outline";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";

export default {
    name: 'ProjectExportBudgetsByBudgetDeadlineModal',
    components: {
        DocumentReportIcon,
        DisclosurePanel,
        DisclosureButton,
        Disclosure,
        XIcon,
        AddButton,
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
                this.showMandatoryFieldsErrorText = false;

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
