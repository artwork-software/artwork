<template>
    <ArtworkBaseModal @close="close()" description="" modal-size="max-w-4xl" :title="activeTab">
        <div class="p-3">
            <ul class="tab-container mb-4">
                <li v-if="props.enums.length > 1"
                    v-for="(tab) in props.enums"
                    @click="activeTab = tab"
                    :class="[activeTab === tab ? 'bg-accent-50 text-accent-700 ring-1 ring-inset ring-accent-600/20'
                : 'text-text-muted hover:text-text hover:bg-surface-sunken',
              'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium cursor-pointer'
            ]">
                    {{ $t(tab) }}
                </li>
            </ul>
            <template v-for="(tab) in props.enums">
                <template v-if="tab === exportTabEnums.PDF_CALENDAR_EXPORT">
                    <PdfCalendarExport v-if="activeTab === exportTabEnums.PDF_CALENDAR_EXPORT"
                                       @close="close()"
                                       @closed="close()"
                                       :project="configuration[exportTabEnums.PDF_CALENDAR_EXPORT]?.project"
                                       :preselected-filters="configuration[exportTabEnums.PDF_CALENDAR_EXPORT]?.user_filters ?? null"
                                       :preselected-date-range="configuration[exportTabEnums.PDF_CALENDAR_EXPORT]?.date_range ?? null"/>
                </template>
                <template v-else-if="tab === exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT">
                    <PdfMonthlyCalendarExport v-if="activeTab === exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT"
                                              @close="close()"
                                              @closed="close()"
                                              :project="configuration[exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT]?.project"
                                              :preselected-filters="configuration[exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT]?.user_filters ?? null"
                                              :preselected-date-range="configuration[exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT]?.date_range ?? null"/>
                </template>
                <template v-else-if="tab === exportTabEnums.PDF_SHIFT_PLAN_EXPORT">
                    <PdfShiftPlanExport v-if="activeTab === exportTabEnums.PDF_SHIFT_PLAN_EXPORT"
                                        @close="close()"
                                        :configuration="configuration[exportTabEnums.PDF_SHIFT_PLAN_EXPORT]"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT">
                    <ExcelBudgetByBudgetDeadlineExport v-if="activeTab === exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT" @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_EVENT_LIST_EXPORT">
                    <ExcelEventListOrCalendarExport v-if="activeTab === exportTabEnums.EXCEL_EVENT_LIST_EXPORT"
                                                    :export-tab-enum="exportTabEnums.EXCEL_EVENT_LIST_EXPORT"
                                                    :show-artists="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT].show_artists"
                                                    :project-preselect="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT]?.project ?? null"
                                                    :preselected-filters="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT]?.user_filters ?? null"
                                                    :preselected-date-range="configuration[exportTabEnums.EXCEL_EVENT_LIST_EXPORT]?.date_range ?? null"
                                                    @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_CALENDAR_EXPORT">
                    <ExcelEventListOrCalendarExport v-if="activeTab === exportTabEnums.EXCEL_CALENDAR_EXPORT"
                                                    :export-tab-enum="exportTabEnums.EXCEL_CALENDAR_EXPORT"
                                                    :project-preselect="configuration[exportTabEnums.EXCEL_CALENDAR_EXPORT]?.project ?? null"
                                                    :preselected-filters="configuration[exportTabEnums.EXCEL_CALENDAR_EXPORT]?.user_filters ?? null"
                                                    :preselected-date-range="configuration[exportTabEnums.EXCEL_CALENDAR_EXPORT]?.date_range ?? null"
                                                    @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_WORK_TIME_OVERVIEW_EXPORT">
                    <ExcelWorkTimeOverviewExport v-if="activeTab === exportTabEnums.EXCEL_WORK_TIME_OVERVIEW_EXPORT"
                                                 :crafts="configuration[exportTabEnums.EXCEL_WORK_TIME_OVERVIEW_EXPORT]?.crafts ?? []"
                                                 @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_CRAFT_DISTRIBUTION_EXPORT">
                    <ExcelCraftDistributionExport v-if="activeTab === exportTabEnums.EXCEL_CRAFT_DISTRIBUTION_EXPORT"
                                                  :crafts="configuration[exportTabEnums.EXCEL_CRAFT_DISTRIBUTION_EXPORT]?.crafts ?? []"
                                                  @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_PROJECT_ROLE_MATRIX_EXPORT">
                    <ExcelProjectRoleMatrixExport v-if="activeTab === exportTabEnums.EXCEL_PROJECT_ROLE_MATRIX_EXPORT"
                                                  @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.PDF_DAILY_PROJECT_SHIFT_PLAN_EXPORT">
                    <PdfDailyProjectShiftPlanExport v-if="activeTab === exportTabEnums.PDF_DAILY_PROJECT_SHIFT_PLAN_EXPORT"
                                                    :configuration="configuration[exportTabEnums.PDF_DAILY_PROJECT_SHIFT_PLAN_EXPORT]"
                                                    @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.EXCEL_SHIFT_PERSONNEL_PLAN_EXPORT">
                    <ExcelShiftPersonnelPlanExport v-if="activeTab === exportTabEnums.EXCEL_SHIFT_PERSONNEL_PLAN_EXPORT"
                                                   :configuration="configuration[exportTabEnums.EXCEL_SHIFT_PERSONNEL_PLAN_EXPORT]"
                                                   @close="close()"/>
                </template>
                <template v-else-if="tab === exportTabEnums.PDF_SEASON_SCHEDULE_EXPORT">
                    <PdfSeasonScheduleExport v-if="activeTab === exportTabEnums.PDF_SEASON_SCHEDULE_EXPORT"
                                             @close="close()"
                                             @closed="close()"
                                             :preselected-filters="configuration[exportTabEnums.PDF_SEASON_SCHEDULE_EXPORT]?.user_filters ?? null"
                                             :preselected-date-range="configuration[exportTabEnums.PDF_SEASON_SCHEDULE_EXPORT]?.date_range ?? null"/>
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
    PdfMonthlyCalendarExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/PdfMonthlyCalendarExport.vue")
    ),
    PdfShiftPlanExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/PdfShiftPlanExport.vue")
    ),
    ExcelBudgetByBudgetDeadlineExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelBudgetByBudgetDeadlineExport.vue")
    ),
    ExcelEventListOrCalendarExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelEventListOrCalendarExport.vue")
    ),
    ExcelWorkTimeOverviewExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelWorkTimeOverviewExport.vue")
    ),
    ExcelCraftDistributionExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelCraftDistributionExport.vue")
    ),
    ExcelProjectRoleMatrixExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelProjectRoleMatrixExport.vue")
    ),
    PdfDailyProjectShiftPlanExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/PdfDailyProjectShiftPlanExport.vue")
    ),
    ExcelShiftPersonnelPlanExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/ExcelShiftPersonnelPlanExport.vue")
    ),
    PdfSeasonScheduleExport = defineAsyncComponent(
        () => import("@/Layouts/Components/Export/Tabs/PdfSeasonScheduleExport.vue")
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
