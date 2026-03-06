<template>
    <ShiftHeader>
        <div class="w-full bg-white">
            <ShiftPlanListViewFunctionBar
                ref="functionBarRef"
                :date-value="dateValue"
                :filter-options="filterOptions"
                :personal-filters="personalFilters"
                :user_filters="user_filters"
                :crafts="crafts"
                :filter-type="filterType"
                @openHistoryModal="showHistoryModal = true"
            >
                <template #moreButtons>
                    <SwitchIconTooltip
                        v-model="multiEditMode"
                        :tooltip-text="$t('Multi-Edit')"
                        size="md"
                        icon="IconPencil"
                        @change="onMultiEditToggle"
                    />
                </template>
            </ShiftPlanListViewFunctionBar>

            <!-- Multi-edit action bar -->
            <div v-if="multiEditMode" ref="multiEditBarRef" class="sticky z-40 bg-white border-b border-zinc-200 px-5 py-2 flex items-center gap-x-4" :style="{ top: functionBarHeight + 'px' }">
                <span class="text-sm text-gray-600">{{ selectedShiftIds.length }} {{ $t('selected') }}</span>
                <button
                    type="button"
                    :disabled="selectedShiftIds.length === 0"
                    @click="duplicateSelectedShifts"
                    :class="[selectedShiftIds.length === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 cursor-pointer', 'rounded-md px-4 py-1.5 text-sm font-semibold text-white shadow-sm']"
                >
                    {{ $t('Duplicate') }}
                </button>
                <button
                    type="button"
                    :disabled="selectedShiftIds.length === 0"
                    @click="showDeleteConfirm = true"
                    :class="[selectedShiftIds.length === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-artwork-error hover:bg-artwork-error/90 cursor-pointer', 'rounded-md px-4 py-1.5 text-sm font-semibold text-white shadow-sm']"
                >
                    {{ $t('Delete') }}
                </button>
            </div>

            <div v-if="calendarWarningText" class="px-5 py-2 bg-yellow-50 text-yellow-800 text-sm">
                {{ calendarWarningText }}
            </div>

            <div class="pb-10">
                <template v-if="hasShifts">
                    <div v-for="dayData in groupedShifts" :key="dayData.day">
                        <!-- Day header — black sticky bar -->
                        <div
                            class="sticky z-30 bg-artwork-navigation-background text-white px-4 py-2.5 font-semibold text-base rounded-r-lg"
                            :style="{ top: dayHeaderStickyTop + 'px' }"
                        >
                            {{ formatDayHeader(dayData.day) }}
                        </div>

                        <div v-for="roomData in dayData.rooms" :key="roomData.room_id">
                            <!-- Room header -->
                            <div class="flex items-center justify-between px-4 py-2.5 text-xs border-1 shadow-sm font-semibold text-gray-600 uppercase tracking-wide border-gray-200 bg-gray-50 rounded-r-lg">
                                <span>{{ roomData.room ? roomData.room.name : $t('No room') }}</span>
                                <ToolTipComponent
                                    v-if="(can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                    direction="left"
                                    :tooltip-text="$t('Add Shift')"
                                    icon="IconPlus"
                                    icon-size="size-4"
                                    @click="openAddShiftForRoomAndDay(dayData.day, roomData.room?.id ?? null)"
                                    classes-button="border border-zinc-200 inline-flex items-center justify-center cursor-pointer rounded-md size-6 text-sm font-medium bg-white hover:bg-gray-50 transition duration-200 ease-in-out mr-2"
                                />
                            </div>

                            <!-- Shifts -->
                            <template v-for="shift in roomData.shifts" :key="shift.id">
                                <div class="flex items-center gap-3 border-b border-gray-100 py-1.5 px-2 border-l-4"
                                     :style="{ backgroundColor: hexToRgba(shift.craft?.color, 0.12), borderLeftColor: shift.craft?.color || '#d1d5db' }">
                                    <!-- Multi-edit checkbox -->
                                    <div v-if="multiEditMode" class="shrink-0">
                                        <input
                                            type="checkbox"
                                            :checked="selectedShiftIds.includes(shift.id)"
                                            @change="toggleShiftSelection(shift.id)"
                                            class="input-checklist"
                                        />
                                    </div>

                                    <!-- Shift info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-x-2">
                                            <!-- Craft abbreviation + time -->
                                            <div class="flex items-center gap-x-1.5 text-sm">
                                                <PropertyIcon name="IconLock" class="h-3 w-3 text-black" stroke-width="2" v-if="shift.is_committed" />
                                                <span v-if="shift.shiftGroup && listViewSettings.show_shift_group_tag" class="text-[10px] text-gray-400">({{ shift.shiftGroup.name }})</span>
                                                <span class="font-medium" :style="{ color: shift.craft?.color }">{{ shift.craft?.abbreviation }}</span>
                                                <span>{{ shift.start }} - {{ shift.end }}</span>
                                            </div>

                                            <!-- Total count with status dot -->
                                            <div class="flex items-center text-xs text-gray-500">
                                                ({{ getUsedWorkerCount(shift) }}/{{ getMaxWorkerCount(shift) }})
                                                <span class="inline-block w-2 h-2 rounded-full ml-1"
                                                      :class="{
                                                        'bg-red-500': getUsedWorkerCount(shift) === 0 && getMaxWorkerCount(shift) !== 0,
                                                        'bg-yellow-500': getUsedWorkerCount(shift) > 0 && getUsedWorkerCount(shift) < getMaxWorkerCount(shift),
                                                        'bg-green-500': getUsedWorkerCount(shift) >= getMaxWorkerCount(shift)
                                                      }">
                                                </span>
                                            </div>

                                            <!-- Individual qualification lines -->
                                            <div class="flex flex-row flex-wrap gap-x-2 text-[11px] text-gray-400">
                                                <div
                                                    v-for="row in getQualificationRows(shift)"
                                                    :key="row.shift_qualification_id"
                                                    class="flex items-center"
                                                >
                                                    {{ row.workerCount }}/{{ row.maxWorkerCount }}
                                                    <PropertyIcon
                                                        stroke-width="1"
                                                        class="text-black size-3 ml-0.5"
                                                        :name="getShiftQualificationIcon(row.shift_qualification_id)"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Shift notes -->
                                        <div v-if="listViewSettings.shift_notes && shift.description" class="text-xs text-gray-400 mt-0.5 pl-0.5">
                                            {{ shift.description }}
                                        </div>
                                    </div>

                                    <!-- Project name as tag -->
                                    <div v-if="getProject(shift)" class="shrink-0">
                                        <Link
                                            :href="getProjectShiftTabUrl(shift)"
                                            class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-black underline hover:bg-gray-50 transition"
                                        >
                                            {{ getProject(shift).name }}
                                        </Link>
                                    </div>

                                    <!-- 3-dot menu -->
                                    <div class="shrink-0">
                                        <BaseMenu white-menu-background>
                                            <BaseMenuItem
                                                icon="IconEdit"
                                                :title="$t('Edit shift')"
                                                white-menu-background
                                                @click="openEditShift(shift)"
                                            />
                                            <BaseMenuItem
                                                icon="IconCalendarEvent"
                                                :title="$t('Show in shift plan')"
                                                white-menu-background
                                                @click="navigateToShiftPlan(shift, dayData.day)"
                                            />
                                        </BaseMenu>
                                    </div>
                                </div>

                                <!-- Detailed function overview -->
                                <div v-if="listViewSettings.detailed_shift_overview" class="ml-2 mb-1">
                                    <SingleShiftInDailyShiftView
                                        :shift="normalizeShift(shift)"
                                        :shift-qualifications="shiftQualifications"
                                        :crafts="crafts"
                                        :first_project_calendar_tab_id="firstProjectShiftTabId"
                                        :has-collision="false"
                                        details-only
                                    />
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

                <div v-else class="flex items-center justify-center py-20 text-gray-400 text-sm">
                    {{ $t('No shifts found for the selected time range.') }}
                </div>
            </div>
        </div>

        <!-- Add/Edit Shift Modal -->
        <AddShiftModal
            v-if="showAddShiftModal"
            :crafts="crafts"
            :event="null"
            :shift="shiftToEdit"
            :current-user-crafts="currentUserCrafts"
            :buffer="null"
            :shift-qualifications="shiftQualifications"
            :shift-groups="shiftGroups"
            :global-qualifications="globalQualifications"
            @closed="closeAddShiftModal"
            :shift-time-presets="shiftTimePresets"
            :rooms="rooms"
            :room="roomForShiftAdd"
            :day="dayForShiftAdd"
            :shift-plan-modal="true"
            :edit="shiftToEdit !== null"
        />

        <!-- History Modal -->
        <ShiftHistoryModal
            v-if="showHistoryModal"
            :crafts="crafts"
            :initialStartDate="dateValue[0]"
            :initialEndDate="dateValue[1]"
            @close="showHistoryModal = false"
        />

        <!-- Delete confirmation -->
        <ConfirmDeleteModal
            v-if="showDeleteConfirm"
            @closed="showDeleteConfirm = false"
            @delete="deleteSelectedShifts"
            :title="$t('Delete shifts')"
            :description="$t('Are you sure you want to delete the selected shifts?')"
            :button="$t('Delete')"
        />
    </ShiftHeader>
</template>

<script setup>
import {ref, computed, onMounted, onBeforeUnmount, nextTick, defineAsyncComponent} from 'vue';
import {Link, router, usePage} from '@inertiajs/vue3';
import {useI18n} from 'vue-i18n';
import {usePermission} from '@/Composeables/Permission.js';
import axios from 'axios';
import ShiftHeader from '@/Pages/Shifts/ShiftHeader.vue';
import ShiftPlanListViewFunctionBar from '@/Layouts/Components/ShiftPlanComponents/ShiftPlanListViewFunctionBar.vue';
import BaseMenu from '@/Components/Menu/BaseMenu.vue';
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue';
import SwitchIconTooltip from '@/Artwork/Toggles/SwitchIconTooltip.vue';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';

const AddShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/AddShiftModal.vue'),
    delay: 200,
    timeout: 5000,
});

const SingleShiftInDailyShiftView = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue'),
    delay: 200,
});

const ShiftHistoryModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftHistoryModal.vue'),
    delay: 200,
    timeout: 3000,
});

const {t: $t, locale} = useI18n();
const {can, hasAdminRole} = usePermission(usePage().props);

const props = defineProps({
    groupedShifts: Array,
    dateValue: Array,
    listViewSettings: Object,
    user_filters: Object,
    crafts: Array,
    eventTypes: Array,
    filterOptions: Object,
    personalFilters: Array,
    shiftQualifications: Array,
    firstProjectShiftTabId: [String, Number],
    filterType: String,
    calendarWarningText: String,
    rooms: [Array, Object],
    shiftTimePresets: Array,
    shiftGroups: [Array, Object],
    globalQualifications: [Array, Object],
    currentUserCrafts: Array,
});

// Sticky offset measurement
const functionBarRef = ref(null);
const multiEditBarRef = ref(null);
const functionBarHeight = ref(0);
let resizeObserver = null;

const dayHeaderStickyTop = computed(() => {
    let top = functionBarHeight.value;
    if (multiEditMode.value && multiEditBarRef.value) {
        top += multiEditBarRef.value.offsetHeight;
    }
    return top;
});

onMounted(() => {
    nextTick(() => {
        const el = functionBarRef.value?.$el || functionBarRef.value;
        if (el) {
            functionBarHeight.value = el.offsetHeight;
            resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    functionBarHeight.value = entry.target.offsetHeight;
                }
            });
            resizeObserver.observe(el);
        }
    });
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});

// Multi-edit state
const multiEditMode = ref(false);
const selectedShiftIds = ref([]);

// Shift add/edit state
const showAddShiftModal = ref(false);
const shiftToEdit = ref(null);
const roomForShiftAdd = ref(null);
const dayForShiftAdd = ref(null);

// History modal
const showHistoryModal = ref(false);

// Delete confirm
const showDeleteConfirm = ref(false);

// Normalize shift data: Laravel serializes serviceProvider relation as service_provider,
// but SingleShiftInDailyShiftView expects serviceProviders
const normalizeShift = (shift) => ({
    ...shift,
    serviceProviders: shift.service_provider || shift.serviceProvider || shift.serviceProviders || [],
});

const hasShifts = computed(() => {
    return props.groupedShifts && props.groupedShifts.length > 0;
});

const formatDayHeader = (dateString) => {
    const date = new Date(dateString);
    const options = {weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit'};
    return date.toLocaleDateString(locale.value === 'de' ? 'de-DE' : 'en-US', options);
};

const getProject = (shift) => {
    return shift.project ?? shift.event?.project ?? null;
};

const getProjectShiftTabUrl = (shift) => {
    const project = getProject(shift);
    if (!project) return '#';
    return route('projects.tab', {project: project.id, projectTab: props.firstProjectShiftTabId});
};

// Craft color helper — same pattern as DailyRoomSplitTimeline
const hexToRgba = (hex, alpha = 0.12) => {
    if (!hex) return 'transparent';
    try {
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) {
            hex = hex.split('').map(c => c + c).join('');
        }
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    } catch {
        return 'transparent';
    }
};

// Worker count helpers
const getMaxWorkerCount = (shift) => {
    let count = 0;
    shift.shifts_qualifications?.forEach((sq) => {
        count += sq.value ?? 0;
    });
    return count;
};

const getUsedWorkerCount = (shift) => {
    return (shift.users?.length || 0) +
        (shift.freelancer?.length || 0) +
        (shift.service_provider?.length || shift.serviceProvider?.length || shift.serviceProviders?.length || 0);
};

const getQualificationRows = (shift) => {
    const rows = [];
    shift.shifts_qualifications?.forEach((sq) => {
        if (!sq.value || sq.value === 0) return;
        let assigned = 0;
        shift.users?.forEach((u) => {
            if (u.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++;
        });
        shift.freelancer?.forEach((f) => {
            if (f.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++;
        });
        (shift.service_provider || shift.serviceProvider || shift.serviceProviders || []).forEach((p) => {
            if (p.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++;
        });
        rows.push({
            shift_qualification_id: sq.shift_qualification_id,
            maxWorkerCount: sq.value,
            workerCount: assigned,
        });
    });
    return rows;
};

const getShiftQualificationIcon = (id) => {
    const qual = props.shiftQualifications?.find((q) => q.id === id);
    return qual?.icon ?? null;
};

// Navigation to shift plan (always weekly view) with highlight + scroll
const navigateToShiftPlan = async (shift, dayString) => {
    const shiftDate = new Date(dayString);
    const dayOfWeek = shiftDate.getDay();
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    const daysToSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek;

    const monday = new Date(shiftDate);
    monday.setDate(shiftDate.getDate() - daysToMonday);
    const sunday = new Date(shiftDate);
    sunday.setDate(shiftDate.getDate() + daysToSunday);

    const userId = usePage().props.auth.user.id;

    // Ensure weekly (non-daily) view is active
    await axios.patch(route('user.update.daily_view', userId), { daily_view: false });

    router.patch(route('update.user.shift.calendar.filter.dates', userId), {
        start_date: monday.toISOString().slice(0, 10),
        end_date: sunday.toISOString().slice(0, 10),
        isDailyView: false,
    }, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            router.visit(route('shifts.plan') + '?highlightShift=' + shift.id);
        }
    });
};

// Shift edit
const openEditShift = (shift) => {
    shiftToEdit.value = shift;
    showAddShiftModal.value = true;
};

// Add shift for room/day
const openAddShiftForRoomAndDay = (day, roomId) => {
    shiftToEdit.value = null;
    roomForShiftAdd.value = roomId;
    dayForShiftAdd.value = day;
    showAddShiftModal.value = true;
};

const closeAddShiftModal = () => {
    showAddShiftModal.value = false;
    shiftToEdit.value = null;
    roomForShiftAdd.value = null;
    dayForShiftAdd.value = null;
    router.reload();
};

// Multi-edit
const onMultiEditToggle = () => {
    if (!multiEditMode.value) {
        selectedShiftIds.value = [];
    }
};

const toggleShiftSelection = (shiftId) => {
    const idx = selectedShiftIds.value.indexOf(shiftId);
    if (idx >= 0) {
        selectedShiftIds.value.splice(idx, 1);
    } else {
        selectedShiftIds.value.push(shiftId);
    }
};

const deleteSelectedShifts = () => {
    axios.post(route('shifts.multi.delete'), {
        shift_ids: selectedShiftIds.value,
    }).then(() => {
        selectedShiftIds.value = [];
        showDeleteConfirm.value = false;
        router.reload();
    });
};

const duplicateSelectedShifts = () => {
    axios.post(route('shifts.multi.duplicate'), {
        shift_ids: selectedShiftIds.value,
    }).then(() => {
        selectedShiftIds.value = [];
        router.reload();
    });
};
</script>
