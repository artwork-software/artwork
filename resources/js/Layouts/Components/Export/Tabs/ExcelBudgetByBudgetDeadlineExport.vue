<template>
    <div class="flex flex-col gap-y-1.5">
        <h1 class="headline1 -my-2" style="font-size:18px;">{{ $t('EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT')}}</h1>
        <h2 class="text-sm text-gray-500">
            {{ $t('All project budgets whose budget key date is between the following dates are exported.') }}
        </h2>
        <SwitchGroup as="div" class="mt-2.5 flex items-center gap-x-2">
            <SwitchLabel as="span" class="text-sm cursor-pointer">
                <span :class="!generateDetailedExport ? 'text-black font-bold' : 'xsLight'">
                    {{ $t('Aggregated projects') }}
                </span>
            </SwitchLabel>
            <Switch v-model="generateDetailedExport" :class="[generateDetailedExport ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true" :class="[generateDetailedExport ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
            </Switch>
            <SwitchLabel as="span" class="text-sm cursor-pointer">
                <span :class="generateDetailedExport ? 'text-black font-bold' : 'xsLight'">
                    {{ $t('Itemised projects') }}
                </span>
            </SwitchLabel>
        </SwitchGroup>
        <div class="flex flex-row gap-x-2">
            <BaseInput type="date" id="startDate" :label="$t('Start date')" v-model="startBudgetDeadline"/>
            <BaseInput type="date" id="startDate" :label="$t('End date')" v-model="endBudgetDeadline"/>
        </div>
        <span v-if="showMandatoryFieldsErrorText" class="mt-3 text-red-600 text-xs text-center">
            {{ $t('You must specify both the start and end date. Then start the export again.') }}
        </span>
        <BaseButton class="mt-4 w-40 gap-x-2 self-center justify-center"
                    @click="downloadExportProjectBudgetsByBudgetDeadline()"
                    :text="$t('Export')">
            <DocumentReportIcon class="h-4 w-4"/>
        </BaseButton>
    </div>
</template>

<script setup>
import {DocumentReportIcon} from "@heroicons/vue/outline";
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const emits = defineEmits(['close']),
    startBudgetDeadline = ref(null),
    endBudgetDeadline = ref(null),
    showMandatoryFieldsErrorText = ref(false),
    generateDetailedExport = ref(false),
    downloadExportProjectBudgetsByBudgetDeadline = () => {
    if (startBudgetDeadline.value === null || endBudgetDeadline.value === null) {
        showMandatoryFieldsErrorText.value = true;
    } else {
        if (showMandatoryFieldsErrorText.value) {
            showMandatoryFieldsErrorText.value = false;
        }

        window.open(
            route(
                'projects.export.budgetByBudgetDeadline',
                {
                    startBudgetDeadline: startBudgetDeadline.value,
                    endBudgetDeadline: endBudgetDeadline.value,
                    type: generateDetailedExport.value
                }
            ),
            '_blank',
            'noopener'
        );

        emits.call(this, 'close');
    }
}
</script>
