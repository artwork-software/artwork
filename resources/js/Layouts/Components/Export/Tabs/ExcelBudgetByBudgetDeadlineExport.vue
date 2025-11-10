<template>
    <div class="mx-auto w-full ">
        <div class="flex flex-col space-y-6">

            <!-- Kopfbereich -->
            <section class="r">
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t('EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('All project budgets whose budget key date is between the following dates are exported.') }}
                </p>
            </section>

            <!-- Umschalter Aggregiert / Detailliert -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">

                <SwitchDualLabel
                    v-model="generateDetailedExport"
                    :left-label="$t('Aggregated projects')"
                    :right-label="$t('Itemised projects')"
                    size="md"
                    icon="IconGeometry"
                    :disabled="false"
                />
            </section>

            <!-- Zeitraum -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        type="date"
                        id="startDate"
                        :label="$t('Start date')"
                        v-model="startBudgetDeadline"
                    />
                    <BaseInput
                        type="date"
                        id="endDate"
                        :label="$t('End date')"
                        v-model="endBudgetDeadline"
                    />
                </div>

                <p v-if="showMandatoryFieldsErrorText" class="mt-3 text-xs text-red-600 text-center">
                    {{ $t('You must specify both the start and end date. Then start the export again.') }}
                </p>
            </section>

            <!-- Aktion -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="downloadExportProjectBudgetsByBudgetDeadline()"
                    :label="$t('Export')"
                    icon="IconFileExport"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup>
import { DocumentReportIcon } from "@heroicons/vue/outline";
import { Switch, SwitchGroup } from "@headlessui/vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import { ref } from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";

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
