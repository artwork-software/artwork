<template>
    <BaseModal @closed="$emit('close')">

        <div class="mt-4">
            <ModalHeader
                :title="$t('Details of the Selected Cell')"
                :description="$t('Here you can view the relevant budget data for the selected cell. The displayed values come from the linked sub-projects and were determined based on the matching criteria of the group project.')"
                />
        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
            <div class="xsLight">
                {{ $t('Project') }}
            </div>
            <div class="xsLight">
                {{ $t('Column') }}
            </div>
            <div class="xsLight">
                {{ $t('Value') }}
            </div>
        </div>

        <div class="space-y-3 divide-y divide-gray-200 divide-dashed mb-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3" v-for="row in data">
                <div class="xsDark">
                    {{ row.subProjectName }}
                </div>
                <div class="xsLight">
                    {{ row.relevantColumnName }}
                </div>
                <div class="font-bold text-sm" :class="row.type === 'BUDGET_TYPE_COST' ? 'text-red-500' : 'text-green-500'">
                   <span v-if="row.type === 'BUDGET_TYPE_COST'">-</span>
                   <span v-else>+</span>
                    {{ toCurrencyString(row.value) }} €
                </div>
            </div>
        </div>

    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";

const props = defineProps({
    data: {
        type: Array,
        required: true,
        default() {
            return []
        }
    }
})

const emits = defineEmits(['close'])

const toCurrencyString = (value) => {
    if (value === null || typeof value === 'undefined') {
        value = "0";
    }
    //cast value to String, replace commas by dots. Parse Number and format it to 1.234,56
    return Number(
        String(value).replace(',', '.')
    ).toLocaleString(
        'de-DE',
        {
            minimumFractionDigits: 2
        }
    );
}

</script>

<style scoped>

</style>