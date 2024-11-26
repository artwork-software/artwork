<template>
    <BaseModal @closed="close()">
        <div class="export-modal-container">
            <ul class="tab-container">
                <li v-for="(tab) in props.exportTabEnums"
                    @click="activeTab = tab"
                    :class="[activeTab === tab ? 'active' : '', 'tab']">
                    {{ t(tab) }}
                </li>
            </ul>
            <template v-if="activeTab === exportTabEnums.PDF_CALENDAR_EXPORT">

            </template>
            <template v-if="activeTab === exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT">
                <BudgetByBudgetDeadlineExport @close="close()"/>
            </template>
            <template v-if="activeTab === exportTabEnums.EXCEL_EVENT_LIST_EXPORT">

            </template>
            <template v-if="activeTab === exportTabEnums.EXCEL_CALENDAR_EXPORT">

            </template>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {defineAsyncComponent, ref} from "vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import {useTranslation} from "@/Composeables/Translation.js";

const exportTabEnums = useExportTabEnums(),
    BudgetByBudgetDeadlineExport = defineAsyncComponent(() =>
        import("@/Layouts/Components/Export/Tabs/BudgetByBudgetDeadlineExport.vue")
    ),
    t = useTranslation(),
    emits = defineEmits(['close']),
    props = defineProps({
        exportTabEnums: {
            type: Array,
            required: true,
            validator(tabConstants) {
                if (
                    tabConstants.length > 0 &&
                    tabConstants.every((tabConstant) => Object.values(useExportTabEnums()).includes(tabConstant))
                ) {
                    return true;
                }

                throw new Error('Please provide at least one ExportTabEnum');
            }
        }
    }),
    activeTab = ref(props.exportTabEnums[0]),
    close = () => {
        emits.call(this, 'close');
    };
</script>
