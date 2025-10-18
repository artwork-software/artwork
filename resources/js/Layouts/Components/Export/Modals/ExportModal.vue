<template>
    <ArtworkBaseModal @close="close()" description="" modal-size="max-w-4xl" :title="activeTab">
        <div class="p-3">
            <ul class="tab-container mb-4">
                <li v-if="props.enums.length > 1"
                    v-for="(tab) in props.enums"
                    @click="activeTab = tab"
                    :class="[activeTab === tab
                ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'
                : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50',
              'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium cursor-pointer'
            ]">
                    {{ $t(tab) }}
                </li>
            </ul>
            <template v-for="(tab) in props.enums">
                <template v-if="tab === exportTabEnums.PDF_CALENDAR_EXPORT">
                    <PdfCalendarExport v-if="activeTab === exportTabEnums.PDF_CALENDAR_EXPORT"
                                       @close="close()"
                                       :project="configuration[exportTabEnums.PDF_CALENDAR_EXPORT]?.project"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT">
                    <ExcelBudgetByBudgetDeadlineExport v-if="activeTab === exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT" @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_EVENT_LIST_EXPORT">
                    <ExcelEventListOrCalendarExport v-if="activeTab === exportTabEnums.EXCEL_EVENT_LIST_EXPORT"
                                                    :export-tab-enum="exportTabEnums.EXCEL_EVENT_LIST_EXPORT"
                                                    :show-artists="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT].show_artists"
                                                    :project-preselect="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT]?.project ?? null"
                                                    @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_CALENDAR_EXPORT">
                    <ExcelEventListOrCalendarExport v-if="activeTab === exportTabEnums.EXCEL_CALENDAR_EXPORT"
                                                    :export-tab-enum="exportTabEnums.EXCEL_CALENDAR_EXPORT"
                                                    :project-preselect="configuration[exportTabEnums.EXCEL_CALENDAR_EXPORT]?.project ?? null"
                                                    @close="close()"/>
                </template>
                <template v-else>
                    {{ throwUndefinedEnumUsed() }}
                </template>
            </template>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {defineAsyncComponent, ref} from "vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import {useTranslation} from "@/Composeables/Translation.js";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

const exportTabEnums = useExportTabEnums(),
    $t = useTranslation(),
    emits = defineEmits(['close']),
    PdfCalendarExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/PdfCalendarExport.vue")
    ),
    ExcelBudgetByBudgetDeadlineExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelBudgetByBudgetDeadlineExport.vue")
    ),
    ExcelEventListOrCalendarExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelEventListOrCalendarExport.vue")
    ),
    props = defineProps({
        enums: {
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
        },
        configuration: {
            type: Object,
            required: false,
            default: {}
        }
    }),
    activeTab = ref(props.enums[0]),
    close = () => {
        emits.call(this, 'close');
    },
    throwUndefinedEnumUsed = () => {
        throw new Error('Undefined enum used in ExportModal.');
    };
</script>
