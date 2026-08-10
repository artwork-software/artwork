<template>
    <!-- Container: unterscheidet Kollision/Nicht-Kollision -->
    <div v-if="!detailsOnly" :class="['w-full min-w-64 rounded-lg select-none border']"
         :style="{ backgroundColor: `${fullCraft.color ?? '#999999'}${isFollowUpDay ? '30' : '50'}`, borderColor: isFollowUpDay ? '#d1d5db' : borderColor }">
        <!-- Linke Spalte: Zeilenstruktur -->
        <div class="flex flex-col w-full">
            <!-- Zeile 1: Zeit (niemals umbrechen) + optionale Gruppe + Gewerkname + Menü am Zeilenende -->
            <div class="flex items-center min-w-0 justify-between">
                <div class="flex items-center min-w-0">
                    <div :class="['rounded-md whitespace-nowrap', timePillPadding]" :style="{ backgroundColor: `${fullCraft.color ?? '#999999'}90` }">
                        <span v-if="dayRole === 'end' || dayRole === 'middle'" class="opacity-60">→ </span>{{ displayStartTime }} - {{ displayEndTime }}<span v-if="dayRole === 'start' || dayRole === 'middle'" class="opacity-60"> →</span>
                    </div>
                    <!-- Dienstplanfreigabe: festgeschrieben (Schloss) / angefragt (Pull-Request) -->
                    <ToolTipComponent
                        v-if="shift.isCommitted ?? shift.is_committed"
                        icon="IconLock"
                        icon-size="size-3.5 text-black"
                        :stroke="2"
                        :tooltip-text="$t('Committed')"
                        direction="top"
                        black-icon
                        classes-button="ml-1"
                    />
                    <ToolTipComponent
                        v-else-if="shift.inWorkflow ?? shift.in_workflow"
                        icon="IconGitPullRequest"
                        icon-size="size-3.5 text-black"
                        :stroke="2"
                        :tooltip-text="$t('Requested')"
                        direction="top"
                        black-icon
                        classes-button="ml-1"
                    />
                    <div v-if="shiftGroupResolved && ($page.props.shift_plan_daily_settings ?? $page.props.shift_plan_settings ?? $page.props.auth.user.calendar_settings).show_shift_group_tag" class="text-text-muted" :class="subtitleTextClass">
                        ({{ shiftGroupResolved.name }})
                    </div>
                    <span
                        :class="['ml-1 block truncate text-text cursor-pointer', titleTextClass]"
                        v-tooltip.bottom="{ value: craftTitleFull, class: 'aw-tooltip' }"
                        :aria-label="craftTitleFull"
                        @click.stop="toggleShiftDetails"
                    >
                        {{ fullCraft.name }}
                    </span>
                </div>
                <!-- Menü (wie bei SingleEventInDailyShiftView.vue) -->
                <div v-if="!isFollowUpDay && (can('can plan shifts') || is('artwork admin'))" class="flex items-center shrink-0 pr-1">
                    <div class="flex transition-opacity duration-150">
                        <BaseMenu has-no-offset :dots-color="($page.props.shift_plan_daily_settings ?? $page.props.shift_plan_settings ?? $page.props.auth.user.calendar_settings).high_contrast ? 'text-white' : ''" white-menu-background class="cursor-pointer">
                            <BaseMenuItem white-menu-background v-if="can('can plan shifts') || is('artwork admin')" @click="showAddShiftModal = true" :icon="IconEdit" title="edit" />
                            <BaseMenuItem white-menu-background v-if="can('can plan shifts') || is('artwork admin')" @click="openConfirmDeleteModal" :icon="IconTrash" :title="$t('Delete shift')" />
                        </BaseMenu>
                    </div>
                </div>
            </div>

            <!-- Zeile 2: Gewerkname (gleiches Typo-Level wie Termin-/Projektname) -->
            <div class="min-w-0">
                <!-- Projektfremde Schicht: Projektname bzw. "Ohne Projektzuordnung" als Kontext-Label -->
                <div
                    v-if="isUnrelatedProjectShift"
                    class="ml-2 text-[10px] text-text-subtle italic truncate"
                    v-tooltip.bottom="{ value: unrelatedProjectLabel ?? $t('Without project assignment'), class: 'aw-tooltip' }"
                >
                    {{ unrelatedProjectLabel ?? $t('Without project assignment') }}
                </div>
            </div>

            <!-- Zeile 3: Funktionen (Badges/Liste) -->
            <div class="flex justify-between flex-wrap items-center gap-1 ml-2">
                <div class="flex gap-x-2">
                <div v-for="qualification in shift.shifts_qualifications" :key="qualification.shift_qualification_id">
                    <div class="text-text-subtle text-[10px] flex items-center gap-x-1 ">

                        <div :class="{ 'text-warning font-semibold': getAssignedCountForQualification(qualification.shift_qualification_id) > (qualification.value ?? 0) }">
                            {{ getAssignedCountForQualification(qualification.shift_qualification_id) }}/{{ qualification.value ? qualification.value : '0' }}
                        </div>
                        <ToolTipComponent
                            :icon="findShiftQualification(qualification.shift_qualification_id)?.icon"
                            :tooltip-text="findShiftQualification(qualification.shift_qualification_id)?.name || ''"
                            icon-size="size-5"
                            :stroke="1.75"
                            black-icon
                            classes-button=""
                        />
                    </div>
                </div>
                </div>

                <!-- Globale Qualifikationen (nur Zahlen z.B. 0/2 + Icon mit Tooltip) -->
                <div class="flex gap-x-2 pr-4">
                    <div v-for="gq in demandedGlobalQualifications" :key="'gq-' + gq.id">
                        <div class="text-text-subtle text-[10px] flex items-center gap-x-1">
                            <div>
                                {{ countAssignedForGlobalQualification(gq.id) }}/{{ getGlobalQuantity(gq) }}
                            </div>
                            <ToolTipComponent
                                :icon="findGlobalQualification(gq.id)?.icon"
                                :tooltip-text="findGlobalQualification(gq.id)?.name || ''"
                                icon-size="size-5"
                                :stroke="1.75"
                                black-icon
                                classes-button=""
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shift Description (Anzeigeeinstellung "Notizen einblenden") -->
            <div v-if="showNotes" class="flex items-center gap-x-1 ml-2 mb-1 min-w-0">
                <template v-if="shift.description">
                    <span class="text-xs text-text-muted truncate" v-tooltip.bottom="{ value: shift.description, class: 'aw-tooltip' }">{{ shift.description }}</span>
                    <component
                        v-if="!isFollowUpDay"
                        :is="IconEdit"
                        class="size-4 shrink-0 text-text-subtle hover:text-text-muted cursor-pointer"
                        @click.stop="openDescriptionModal"
                    />
                </template>
                <template v-else-if="!isFollowUpDay">
                    <component
                        :is="IconNote"
                        class="size-4 shrink-0 text-text-subtle hover:text-text-muted cursor-pointer"
                        @click.stop="openDescriptionModal"
                    />
                </template>
            </div>
        </div>
    </div>

        <div v-if="showShiftDetails && !isFollowUpDay" class="mt-1 ml-2 space-y-1">
            <!-- Shift description (im detailsOnly-Modus hier anzeigen, da Header-Card ausgeblendet) -->
            <div v-if="detailsOnly && shift.description" class="text-xs text-text-subtle italic mb-1 pl-1">
                {{ shift.description }}
            </div>

            <template v-for="group in shiftGroups" :key="group.label">
                <div v-for="person in group.items" :key="person.id" class="flex items-center w-full min-w-0 gap-x-2 font-lexend rounded-lg" :class="{ 'border border-dashed border-warning': person.pivot?.is_overbooked }" :style="{ backgroundColor: `${fullCraft.color ?? '#999999'}20` }">
                    <SingleEntityInShift
                        :person="person"
                        :shift="shift"
                        :craft-color="fullCraft.color"
                        :shift-qualifications="shiftQualifications"
                        :has-collision="hasCollision"
                        :force-show-notes="detailsOnly"
                        :prepend-craft-abbreviation="prependCraftAbbreviation"
                        @userRemoved="onChildUserRemoved"
                    />
                </div>
            </template>

            <div v-for="drop in computedShiftQualificationDropElements" :key="`${drop.shift_qualification_id}-${drop.isOverbooked ? 'overbooked' : 'regular'}`" class="flex items-center w-full gap-x-2 font-lexend rounded-lg" :class="drop.isOverbooked ? 'bg-warning-surface border border-dashed border-warning' : 'bg-danger-surface'">
                <Menu as="div" class="relative w-full">
                    <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                        <MenuButton class="flex cursor-pointer items-center gap-x-2 font-lexend rounded-lg w-full" @click="checkShiftCollision(drop.shift_qualification_id, true); loadShiftProjectAssignees()">
                            <!-- Unbesetzt-Balken: gleiche Breite wie Zeit-Pill der zugewiesenen Entity -->
                            <div
                                class="py-1.5 pl-1 pr-0.75 rounded-l-lg"
                                :class="drop.isOverbooked ? 'bg-warning-surface' : 'bg-danger-surface'"
                            >
                                <div
                                    class="text-xs text-left flex items-center gap-x-1 min-w-0 overflow-hidden"
                                    v-tooltip.bottom="{ value: drop.isOverbooked ? $t('Overbooking') : $t('Unoccupied'), appendTo: 'body', class: 'aw-tooltip', position: 'bottom', useTranslation: true }"
                                >
                                    <span v-if="prependCraftAbbreviation && fullCraft?.abbreviation" class="font-semibold shrink-0" :class="drop.isOverbooked ? 'text-warning' : 'text-danger'">{{ fullCraft.abbreviation }}</span>
                                    <component :is="IconInfoTriangle" class="size-4 shrink-0" :class="drop.isOverbooked ? 'text-warning' : 'text-danger'" />
                                    <span class="truncate block">{{ drop.isOverbooked ? $t('Overbooking') : $t('Unoccupied') }}</span>
                                </div>
                            </div>
                            <div class="w-full gap-x-2">
                                <p class="text-xs text-left">{{ drop.requiredDropElementsCount }} {{ findShiftQualification(drop.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}<span v-if="drop.isOverbooked"> ({{ $t('Overbooking') }})</span></p>

                            </div>
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="z-50 rounded-lg shadow-xl ring-1 ring-border-subtle ring-opacity-5 focus:outline-none bg-white max-h-72 flex flex-col">
                                <div class="px-3 py-2 border-b border-border-subtle shrink-0">
                                    <input
                                        type="text"
                                        :value="dropSearchQueries[drop.shift_qualification_id] || ''"
                                        @input="dropSearchQueries[drop.shift_qualification_id] = $event.target.value"
                                        @click.stop
                                        @keydown.stop
                                        :placeholder="$t('Search')"
                                        class="w-full text-xs border border-border rounded-md px-2 py-1 focus:outline-none focus:border-border-strong focus:ring-0"
                                    />
                                </div>
                                <div class="overflow-y-auto">
                                <MenuItem as="div" v-slot="{ active }" v-for="user in filteredAssignablePeople(drop.shift_qualification_id)" :key="user.id" class="px-4 py-2 text-sm text-text-muted hover:bg-surface-sunken cursor-pointer">
                                    <div class="flex justify-between items-center gap-x-2 w-48" @click="createOnDropElementAndSave(user, user.originCraft, drop.shift_qualification_id, drop.isOverbooked) ">
                                        <span class="text-xs truncate w-36" :class="projectAssignmentFor(user) === 'wish' ? 'italic' : ''">{{ user.name || user.full_name }}</span>
                                        <div class="text-xs text-text-subtle flex items-center gap-x-1">
                                            <ToolTipComponent
                                                v-if="projectAssignmentFor(user) === 'binding'"
                                                :icon="IconUserCheck"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="$t('Bindingly assigned to this project')"
                                                direction="top"
                                                classes="text-success w-fit"
                                            />
                                            <ToolTipComponent
                                                v-else-if="projectAssignmentFor(user) === 'wish'"
                                                :icon="IconHeart"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="$t('Has entered a wish for this project')"
                                                direction="top"
                                                classes="text-success w-fit"
                                            />
                                            <ToolTipComponent
                                                :icon="IconId"
                                                icon-size="w-4 h-4"
                                                tooltip-text="Freelancer"
                                                direction="top"
                                                classes="text-text w-fit"
                                                v-if="user.type === 'freelancer'"
                                                use-translation
                                            />
                                            <ToolTipComponent
                                                :icon="IconBuildingCommunity"
                                                icon-size="w-4 h-4"
                                                tooltip-text="ServiceProvider"
                                                direction="top"
                                                classes="text-text w-fit"
                                                v-if="user.type === 'service_provider'"
                                                use-translation
                                            />
                                            <ToolTipComponent
                                                :icon="IconAlertTriangle"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="$t('User already assigned as {0}', [user.qualification])"
                                                direction="top"
                                                classes="text-danger w-fit"
                                                v-if="user.alreadyAssigned"
                                            />
                                            <ToolTipComponent
                                                v-if="user.hasCollision"
                                                :icon="IconClock"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="getCollisionTooltip(user)"
                                                direction="top"
                                                classes="text-danger w-fit"
                                                tooltip-css-class="w-72"
                                            />
                                            <span v-if="user.pivot?.craft_id" class="font-semibold">{{ user.originCraft?.abbreviation || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Float>
                </Menu>
                <component
                    v-if="drop.isOverbooked && canPlanShifts"
                    :is="IconX"
                    class="size-4 mr-2 shrink-0 text-warning hover:text-warning cursor-pointer"
                    v-tooltip.bottom="{ value: $t('Remove overbooking slot'), appendTo: 'body', class: 'aw-tooltip', position: 'bottom', useTranslation: true }"
                    @click.stop="removeOverbookedSlot(drop.shift_qualification_id)"
                />
            </div>

            <!-- "+ Überbuchung" / "+ Schichtplatz" Buttons -->
            <div v-if="canPlanShifts" class="flex items-center w-full gap-x-1">
                <div
                    v-if="allowOverbooking"
                    class="flex items-center w-1/2 font-lexend rounded-lg cursor-pointer hover:bg-opacity-80 transition-colors border border-dashed border-warning bg-warning-surface"
                    @click="showAddOverbookingModal = true"
                >
                    <div class="py-1.5 px-2 flex items-center gap-x-2 w-full text-warning">
                        <component :is="IconCirclePlus" class="size-4 shrink-0" />
                        <span class="text-xs font-medium truncate">{{ $t('Overbooking') }}</span>
                    </div>
                </div>
                <div
                    class="flex items-center gap-x-2 font-lexend rounded-lg cursor-pointer hover:bg-opacity-80 transition-colors border border-dashed border-border-strong"
                    :class="allowOverbooking ? 'w-1/2' : 'w-full'"
                    :style="{ backgroundColor: `${fullCraft.color ?? '#999999'}20` }"
                    @click="showAddFunctionModal = true"
                >
                    <div class="py-1.5 px-2 flex items-center gap-x-2 w-full text-text-muted">
                        <component :is="IconCirclePlus" class="size-4 shrink-0" />
                        <span class="text-xs font-medium truncate">{{ $t('Add Function') }}</span>
                    </div>
                </div>
            </div>
        </div>
    <AddShiftModal
        v-if="showAddShiftModal && !detailsOnly"
        :crafts="crafts"
        :event="null"
        :shift="shift"
        :currentUserCrafts="usePage().props.currentUserCrafts"
        :buffer="null"
        :shift-qualifications="usePage().props.shiftQualifications"
        :shift-groups="usePage().props.shiftGroups"
        :global-qualifications="usePage().props.globalQualifications"
        @closed="showAddShiftModal = false"
        :shift-time-presets="usePage().props.shiftTimePresets"
        :shift-plan-modal="true"
        :edit="shift !== null"
        :rooms="injectedRooms"
        :room="shift?.roomId ?? shift?.room_id ?? null"
    />

    <AddFunctionToShiftModal
        v-if="showAddFunctionModal"
        :shift="shift"
        :shift-qualifications="shiftQualificationsArray"
        :crafts="crafts"
        @close="showAddFunctionModal = false"
    />

    <AddFunctionToShiftModal
        v-if="showAddOverbookingModal"
        :shift="shift"
        :shift-qualifications="shiftQualificationsArray"
        :crafts="crafts"
        is-overbooking
        @close="showAddOverbookingModal = false"
    />

    <!-- Shift Description Modal -->
    <ArtworkBaseModal
        v-if="showDescriptionModal && !detailsOnly"
        :title="$t('Shift description')"
        :description="$t('Add or edit a description for this shift.')"
        @close="showDescriptionModal = false"
    >
        <div class="flex flex-col gap-y-3 mt-2">
            <textarea
                v-model="descriptionDraft"
                class="w-full rounded-lg border border-border p-2 text-sm focus:border-border-strong focus:ring-0"
                rows="4"
                :placeholder="$t('Description')"
            />
            <div class="flex justify-end">
                <BaseUIButton
                    :label="$t('Save')"
                    :icon="IconDeviceFloppy"
                    icon-size="size-4"
                    @click="saveDescription"
                />
            </div>
        </div>
    </ArtworkBaseModal>

    <!-- Bestätigungsmodal: Schicht löschen -->
    <ConfirmationComponent
        v-if="showConfirmDeleteModal && !detailsOnly"
        titel="Schicht löschen"
        description="Möchtest du diese Schicht wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
        @closed="handleConfirmDelete"
    />

    <NotificationToast
        v-model:show="toastVisible"
        :title="toastTitle"
        :description="toastDescription"
        :type="toastType"
    />
</template>

<script setup>
import {ref, computed, watch, defineAsyncComponent, onMounted, onBeforeUnmount, reactive, inject} from "vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Float} from "@headlessui-float/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import SingleEntityInShift from "@/Pages/Shifts/DailyViewComponents/SingleEntityInShift.vue";
import {can, is} from "laravel-permission-to-vuejs";
import {useI18n} from "vue-i18n";
import {
    IconAlertTriangle,
    IconBuildingCommunity,
    IconClock, IconEdit, IconTrash,
    IconId,
    IconInfoTriangle,
    IconCirclePlus,
    IconNote,
    IconDeviceFloppy,
    IconUserCheck,
    IconHeart,
    IconX
} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";

const { resolveCraft, resolveShiftGroup } = useShiftPlanLookups();

// Rooms provided by ShiftPlanDailyView for AddShiftModal
const injectedRooms = inject("shiftPlanRooms", ref([]))

const ConfirmationComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmationComponent.vue'),
    delay: 200,
});

const NotificationToast = defineAsyncComponent({
    loader: () => import('@/Artwork/Feedback/NotificationToast.vue'),
    delay: 200,
});

const AddFunctionToShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/DailyViewComponents/AddFunctionToShiftModal.vue'),
    delay: 200,
});

const canPlanShifts = computed(() => can('can plan shifts') || is('artwork admin'));

const props = defineProps({
    shift: Object,
    // Kann als Array ODER als Objekt (Map) geliefert werden → wir normalisieren unten
    shiftQualifications: [Array, Object],
    crafts: Object,
    first_project_calendar_tab_id: Number | String | null,
    // hasCollision wird von DailyRoomSplitTimeline gesetzt und steuert das kompaktere Design
    hasCollision: {
        type: Boolean,
        default: false
    },
    // detailsOnly: nur Zuweisungsbereich ohne Header-Card anzeigen (für Listenansicht)
    detailsOnly: {
        type: Boolean,
        default: false
    },
    // Wenn true, wird in jeder User-Zeile das Gewerk-Kürzel vor die Uhrzeit gesetzt
    // (genutzt im ShiftPlanListView, wenn "Schichtzeile ausblenden" aktiv ist).
    prependCraftAbbreviation: {
        type: Boolean,
        default: false
    },
    // Position der Schicht innerhalb eines mehrtägigen Zeitraums
    dayRole: {
        type: String,
        default: 'single', // 'single' | 'start' | 'middle' | 'end'
    },
    // Projekt-Schichten-Tab ("Projektfremde Schichten anzeigen"): aktuelle Projekt-ID,
    // damit Schichten anderer Projekte bzw. ohne Projekt gelabelt werden
    currentProjectId: {
        type: [Number, String],
        default: null
    },
});

// Folgetag (End-/Mitteltag): visuell abgehoben, nur Kerninfos
const isFollowUpDay = computed(() => props.dayRole === 'end' || props.dayRole === 'middle')

// Projektfremde Schicht (anderes Projekt oder ohne Projektzuordnung) im Projekt-Schichten-Tab
const isUnrelatedProjectShift = computed(() => {
    if (props.currentProjectId === null || props.currentProjectId === undefined) return false
    const shiftProjectId = props.shift?.projectId ?? props.shift?.project_id ?? null
    return Number(shiftProjectId ?? 0) !== Number(props.currentProjectId)
})

const unrelatedProjectLabel = computed(() =>
    props.shift?.projectName ?? props.shift?.project_name ?? null
)

// Anzeigeeinstellung "Notizen einblenden" (Tagesansicht-Settings mit Fallback-Kette)
const showNotes = computed(() => {
    const pageProps = usePage().props
    const settings = pageProps.shift_plan_daily_settings ?? pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings
    return !!settings?.shift_notes
})

// Normalize time values that may arrive as "HH:MM" or ISO datetime "2026-05-18T10:00:00.000000Z"
function normalizeTime(val) {
    if (!val || typeof val !== 'string') return val
    // Already HH:MM
    if (/^\d{2}:\d{2}$/.test(val)) return val
    // ISO datetime — extract HH:MM from the time portion
    const m = val.match(/T(\d{2}:\d{2})/)
    if (m) return m[1]
    // Fallback: "YYYY-MM-DD HH:MM:SS" style
    const sp = val.match(/(\d{2}:\d{2})(:\d{2})?$/)
    if (sp) return sp[1]
    return val
}

// Angezeigte Zeiten anpassen wenn Schicht über Tagesgrenze geht
const displayStartTime = computed(() => {
    if (props.dayRole === 'end' || props.dayRole === 'middle') return '00:00'
    return normalizeTime(props.shift.start)
})
const displayEndTime = computed(() => {
    if (props.dayRole === 'middle') return '00:00'
    return normalizeTime(props.shift.end)
})

// Initialize i18n
const { t } = useI18n();

const showShiftDetails = ref(true);
const showAddShiftModal = ref(false);
const showAddFunctionModal = ref(false);
const showAddOverbookingModal = ref(false);
const allowOverbooking = computed(() => !!usePage().props.allow_shift_overbooking);
const dropSearchQueries = reactive({});
const showConfirmDeleteModal = ref(false);

const toastVisible = ref(false);
const toastTitle = ref('');
const toastDescription = ref('');
const toastType = ref('success');

const droppedUser = ref({});
const seriesShiftData = ref(null);
// Initialisiere Cache mit leeren Arrays pro Qualifikation
const assignablePeopleCache = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = [];
        return acc;
    }, {})
);

const loadingStates = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = false;
        return acc;
    }, {})
);

// Track when the last request was made for each qualification ID
const lastRequestTime = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = 0;
        return acc;
    }, {})
);

const showDescriptionModal = ref(false);
const descriptionDraft = ref('');

const openDescriptionModal = () => {
    descriptionDraft.value = props.shift.description || '';
    showDescriptionModal.value = true;
};

const saveDescription = async () => {
    try {
        await axios.patch(route('event.shift.update.updateDescription', props.shift.id), {
            description: descriptionDraft.value
        });
        props.shift.description = descriptionDraft.value;
        showDescriptionModal.value = false;
    } catch (error) {
        console.error('Error saving shift description:', error);
    }
};

const emit = defineEmits(['toggle']);
const toggleShiftDetails = () => {
    showShiftDetails.value = !showShiftDetails.value;
    emit('toggle', showShiftDetails.value);
};

// Normalisiere Qualifikationsliste in ein Array, falls als Objekt/Map geliefert
const shiftQualificationsArray = computed(() =>
    Array.isArray(props.shiftQualifications)
        ? props.shiftQualifications
        : Object.values(props.shiftQualifications || {})
);

const findShiftQualification = (id) =>
    shiftQualificationsArray.value.find(q => q.id === id);

// ----- Globale Qualifikationen: Meta + geforderte Qualis und Zählung -----
const globalQualificationsMeta = computed(() => {
    const page = usePage();
    const list = page?.props?.globalQualifications ?? [];
    return Array.isArray(list) ? list : Object.values(list || {});
});

const findGlobalQualification = (id) =>
    globalQualificationsMeta.value.find(q => q.id === id);

const demandedGlobalQualifications = computed(() => {
    const arr = Array.isArray(props.shift?.globalQualifications)
        ? props.shift.globalQualifications
        : Object.values(props.shift?.globalQualifications || {});
    return arr.filter(gq => (gq?.pivot?.quantity ?? gq?.quantity ?? 0) > 0);
});

const getGlobalQuantity = (gq) => (gq?.pivot?.quantity ?? gq?.quantity ?? 0);

const getPersonGlobalQualificationIds = (person) => {
    // Unterstütze mehrere mögliche Property-Namen (camelCase, snake_case, bereits-normalisierte ID-Listen)
    const raw =
        person?.globalQualifications ??
        person?.global_qualifications ??
        person?.globalQualificationIds ??
        person?.global_qualification_ids ??
        [];

    // Wenn bereits eine Liste von IDs vorliegt
    if (Array.isArray(raw) && raw.every((x) => typeof x === 'number')) {
        return raw;
    }

    const arr = Array.isArray(raw) ? raw : Object.values(raw || {});
    let ids = arr
        .map((x) => {
            if (typeof x === 'number') return x;
            return x?.id ?? x?.global_qualification_id ?? null;
        })
        .filter((v) => typeof v === 'number' && Number.isFinite(v));

    // Fallback: falls raw ein Objekt wie {"3": true, "5": 1} ist
    if (ids.length === 0 && raw && !Array.isArray(raw) && typeof raw === 'object') {
        ids = Object.keys(raw)
            .map((k) => Number(k))
            .filter((n) => Number.isFinite(n));
    }

    return ids;
};

// Lokale Deltas, um Zähler für globale Qualifikationen sofort (optimistisch) zu aktualisieren
const globalQualificationDeltas = ref({})

// Lokale Deltas für Shift-Qualifikationen (Besetzung "0/4" → "1/4" sofort aktualisieren)
const shiftQualificationDeltas = ref({})

// Separate Deltas für Überbuchungs-Zuweisungen (zählen nicht gegen den regulären Bedarf)
const overbookedQualificationDeltas = ref({})

const demandedGlobalQualificationIdsSet = computed(() => new Set(demandedGlobalQualifications.value.map(gq => gq.id)))

const countAssignedForGlobalQualificationBase = (globalQualificationId) => {
    const workers = props.shift?.workers || []
    return workers.filter(p => getPersonGlobalQualificationIds(p).includes(globalQualificationId)).length
}

const countAssignedForGlobalQualification = (globalQualificationId) => {
    const base = countAssignedForGlobalQualificationBase(globalQualificationId)
    const delta = globalQualificationDeltas.value[globalQualificationId] ?? 0
    return base + delta
}

const adjustDeltaForUser = (person, direction = 1) => {
    if (!person) return
    const ids = getPersonGlobalQualificationIds(person)
    ids.forEach(id => {
        if (!demandedGlobalQualificationIdsSet.value.has(id)) return
        globalQualificationDeltas.value[id] = (globalQualificationDeltas.value[id] ?? 0) + direction
    })
}

const computedShiftQualificationDropElements = computed(() => {
    const workers = props.shift.workers || []
    const drops = [];
    (props.shift.shifts_qualifications || []).forEach(sq => {
        const id = sq.shift_qualification_id
        const regularAssigned = workers.filter(w => w.pivot?.shift_qualification_id === id && !w.pivot?.is_overbooked).length
        const overbookedAssigned = workers.filter(w => w.pivot?.shift_qualification_id === id && w.pivot?.is_overbooked).length
        const remaining = sq.value - regularAssigned - (shiftQualificationDeltas.value[id] ?? 0)
        if (remaining > 0) {
            drops.push({ shift_qualification_id: id, requiredDropElementsCount: remaining, isOverbooked: false })
        }
        if (allowOverbooking.value) {
            const openOverbooked = (sq.overbooked_value ?? 0) - overbookedAssigned - (overbookedQualificationDeltas.value[id] ?? 0)
            if (openOverbooked > 0) {
                drops.push({ shift_qualification_id: id, requiredDropElementsCount: openOverbooked, isOverbooked: true })
            }
        }
    })
    return drops;
});

const getEmptyShiftQualification = (id) =>
    computedShiftQualificationDropElements.value.find(drop => drop.shift_qualification_id === id && !drop.isOverbooked);

// Tatsächliche Besetzung je Funktion (inkl. Überbuchungen) für die "x/y"-Anzeige
const getAssignedCountForQualification = (id) => {
    const workers = props.shift.workers || []
    const base = workers.filter(w => w.pivot?.shift_qualification_id === id).length
    return base + (shiftQualificationDeltas.value[id] ?? 0) + (overbookedQualificationDeltas.value[id] ?? 0)
}

const removeOverbookedSlot = (shiftQualificationId) => {
    router.patch(route('shifts.qualifications.overbook.decrease', { shift: props.shift.id }), {
        qualification_id: shiftQualificationId
    }, {
        preserveScroll: true
    });
}

const shiftGroups = computed(() => {
    const workers = props.shift.workers || [];
    return [
        { label: 'users', items: workers.filter(w => w.type === 'user') },
        { label: 'freelancers', items: workers.filter(w => w.type === 'freelancer') },
        { label: 'serviceProviders', items: workers.filter(w => w.type === 'service_provider') },
    ];
});

// Debounce time in milliseconds - prevent requests more frequently than this
const DEBOUNCE_TIME = 2000; // 2 seconds

const checkShiftCollision = async (shiftQualificationId, forceRefresh = false) => {
    // Skip if already loading (prevents duplicate requests)
    if (loadingStates.value[shiftQualificationId]) {
        return;
    }

    // Check if we've made a request recently (debounce)
    const now = Date.now();
    const timeSinceLastRequest = now - lastRequestTime.value[shiftQualificationId];

    // Skip if we've made a request recently and not forcing refresh
    if (!forceRefresh && timeSinceLastRequest < DEBOUNCE_TIME && assignablePeopleCache.value[shiftQualificationId]?.length > 0) {
        return;
    }

    // Update last request time
    lastRequestTime.value[shiftQualificationId] = now;

    // Set loading state
    loadingStates.value[shiftQualificationId] = true;

    try {
        // Check if we have the required time parameters
        if (!props.shift.start || !props.shift.end) {
            // Set empty result and return early
            assignablePeopleCache.value[shiftQualificationId] = getAssignablePeople(shiftQualificationId);
            return;
        }

        // Use current date as fallback if date parameters are missing
        const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD

        const people = getAssignablePeople(shiftQualificationId);
        // Don't make the request if there are no people to check
        if (people.length === 0) {
            assignablePeopleCache.value[shiftQualificationId] = [];
            return;
        }

        // Create request parameters with the correct parameter names and fallback values
        // The backend expects start_date and end_date
        const requestParams = {
            people: people.map(p => ({
                id: p.id,
                type: p.type // Backend expects 'user', 'freelancer', or 'service_provider'
            })),
            // Use the correct property names for dates - could be either startDate/endDate or start_date/end_date
            start_date: props.shift.startDate || props.shift.start_date || today,
            end_date: props.shift.endDate || props.shift.end_date || today,
            start: props.shift.start,
            end: props.shift.end,
            shift_id: props.shift.id
        };

        const response = await axios.post(route('shift.check-collisions'), requestParams);

        assignablePeopleCache.value[shiftQualificationId] = people.map(person => {
            const collisionData = response.data.find(d =>
                d.id === person.id && d.type === person.type
            );

            // Ensure hasCollision is set correctly based on collisionShifts
            const hasCollision = collisionData?.hasCollision || false;
            const collisionShifts = collisionData?.collisionShifts || [];

            return {
                ...person,
                // Set hasCollision to true if either the backend says it's true OR there are collision shifts
                hasCollision: hasCollision || collisionShifts.length > 0,
                collisionShifts: collisionShifts
            };
        });
    } catch (error) {
        assignablePeopleCache.value[shiftQualificationId] = getAssignablePeople(shiftQualificationId);
    } finally {
        loadingStates.value[shiftQualificationId] = false;
    }
};

const getAssignablePeople = (shiftQualificationId) => {
    // Validate that the shift has the required properties
    const shiftCraftId = props.shift?.craft?.id ?? props.shift?.craftId;
    if (!props.shift || !shiftCraftId) {
        return [];
    }

    // IDs aller universell einsetzbaren Crafts sammeln
    const universalCraftIds = new Set(
        Object.values(props.crafts || {})
            .filter(c => c.universally_applicable)
            .map(c => c.id)
    );

    // Relevante Craft-IDs: Craft der Schicht + alle universell einsetzbaren
    const relevantCraftIds = new Set([shiftCraftId, ...universalCraftIds]);

    // IDs aller bereits zugewiesenen Personen pro Typ sammeln
    const assigned = { user: [], freelancer: [], service_provider: [] };
    const workers = props.shift.workers || [];
    for (const w of workers) {
        const t = w.type === 0 || w.type === 'user' ? 'user'
            : w.type === 1 || w.type === 'freelancer' ? 'freelancer'
            : 'service_provider';
        assigned[t].push(w.id);
    }

    const peopleWithCraft = [];

    Object.values(props.crafts).forEach(craft => {
        // Nur Crafts verarbeiten, die zur Schicht passen (shiftCraftId oder universally_applicable)
        if (!relevantCraftIds.has(craft.id)) {
            return;
        }

        const personTypes = [
            { type: 'user', list: craft.users || [] },
            { type: 'freelancer', list: craft.freelancers || [] },
            { type: 'service_provider', list: craft.service_providers || craft.serviceProviders || [] }
        ];

        personTypes.forEach(({ type, list }) => {
            list.forEach(person => {
                // Prüfe ob Person zu Schichten zuweisbar ist (can_work_shifts)
                if (person.can_work_shifts === false) {
                    return;
                }

                const key = `${type}-${person.id}`;
                const alreadyAdded = peopleWithCraft.some(p => p.key === key);

                // Prüfe ob die Person die passende Qualifikation hat.
                // Bevorzuge immer die direkte Qualifikation über das Craft der Schicht.
                // Nur wenn keine direkte Qualifikation vorhanden ist, falle auf universelle Crafts zurück.
                const directQualification = person.shift_qualifications?.find(q =>
                    q.id === shiftQualificationId &&
                    q.pivot?.craft_id === shiftCraftId
                );
                const universalQualification = !directQualification
                    ? person.shift_qualifications?.find(q =>
                        q.id === shiftQualificationId &&
                        universalCraftIds.has(q.pivot?.craft_id)
                    )
                    : null;
                const matchingQualification = directQualification || universalQualification;

                // Prüfen, ob Person bereits in der Schicht ist
                const alreadyAssigned = assigned[type]?.includes(person.id);

                if (!alreadyAdded && matchingQualification) {
                    // Bestimme, ob die Zuweisung über ein universelles Gewerk erfolgt
                    const qualCraftId = matchingQualification.pivot?.craft_id;
                    const isViaUniversalCraft = qualCraftId !== shiftCraftId
                        && universalCraftIds.has(qualCraftId);

                    // originCraft muss das Craft sein, über das die Qualifikation kommt (pivot.craft_id),
                    // nicht das Craft aus der äußeren Schleife (craft), da eine Person z.B. dem Schicht-Craft
                    // zugeordnet sein kann, aber die Qualifikation über ein universelles Craft hat.
                    let resolvedOriginCraft = craft;
                    if (isViaUniversalCraft && qualCraftId) {
                        const universalCraft = Object.values(props.crafts || {}).find(c => c.id === qualCraftId);
                        if (universalCraft) {
                            resolvedOriginCraft = universalCraft;
                        }
                    }

                    peopleWithCraft.push({
                        ...person,
                        type,
                        key,
                        alreadyAssigned,
                        qualification: matchingQualification.name || 'Unbekannt',
                        originCraft: {
                            id: resolvedOriginCraft.id,
                            name: resolvedOriginCraft.name,
                            abbreviation: resolvedOriginCraft.abbreviation,
                            color: resolvedOriginCraft.color,
                            universally_applicable: resolvedOriginCraft.universally_applicable
                        },
                        // Pivot-Info für Anzeige der Craft-Abbreviation bei universellen Gewerken
                        pivot: isViaUniversalCraft ? { craft_id: qualCraftId } : null
                    });
                }
            });
        });
    });

    return peopleWithCraft;
};

const filteredAssignablePeople = (shiftQualificationId) => {
    const people = getAssignablePeopleWithCollision(shiftQualificationId);
    const query = (dropSearchQueries[shiftQualificationId] || '').trim().toLowerCase();
    const filtered = query
        ? people.filter(user => {
            const name = (user.name || user.full_name || '').toLowerCase();
            return name.includes(query);
        })
        : people;

    // Dem Projekt Zugeordnete zuoberst (verbindlich vor Wunsch), Rest dahinter
    const rank = (user) => {
        const assignment = projectAssignmentFor(user);
        return assignment === 'binding' ? 0 : assignment === 'wish' ? 1 : 2;
    };

    return [...filtered].sort((a, b) => rank(a) - rank(b));
};

// --- Projektzuordnungen fürs Unbesetzt-Dropdown (einmal je Schicht lazy geladen) ---
const shiftProjectAssignees = ref(null); // Map `${type}_${id}` -> 'binding' | 'wish'
let shiftProjectAssigneesPromise = null;

const loadShiftProjectAssignees = () => {
    if (shiftProjectAssigneesPromise || !props.shift?.id) return;
    shiftProjectAssigneesPromise = axios
        .get(route('shifts.project-assignees', { shift: props.shift.id }))
        .then(({ data }) => {
            const map = new Map();
            (data.assignees ?? []).forEach(a => map.set(`${a.type}_${a.id}`, a.assignment_type));
            shiftProjectAssignees.value = map;
        })
        .catch(() => {
            shiftProjectAssignees.value = new Map();
        });
};

const projectAssignmentFor = (user) =>
    shiftProjectAssignees.value?.get(`${user.type}_${user.id}`) ?? null;

// Angepasste Methode für das Menü, die bereits zugewiesene Personen anzeigt
// Diese Funktion sollte KEINE Requests auslösen, da sie bei jedem Rendering aufgerufen wird
const getAssignablePeopleWithCollision = (shiftQualificationId) => {
    // Wenn Daten im Cache vorhanden sind, diese zurückgeben
    if (assignablePeopleCache.value[shiftQualificationId]?.length > 0) {
        return assignablePeopleCache.value[shiftQualificationId];
    }

    // Wenn keine Daten im Cache sind, getAssignablePeople aufrufen und Cache befüllen
    // Dies zeigt die Benutzer sofort an, auch bevor die Kollisionsprüfung abgeschlossen ist
    const people = getAssignablePeople(shiftQualificationId);
    if (people.length > 0) {
        assignablePeopleCache.value[shiftQualificationId] = people;
    }
    return people;
};

// Function to generate a more informative tooltip for collision warnings
const getCollisionTooltip = (user) => {
    // Basic collision message
    let tooltip = t('Collisions with shifts:');

    // Add details for each collision shift
    const collisionDetails = user.collisionShifts.map(shift => {
        let detail = '';

        // Add craft abbreviation if available
        if (shift.craftAbbreviation) {
            detail += ` ${shift.craftAbbreviation}`;
        }
        // Add time information
        let shiftStartDate = new Date(shift.start);
        let shiftEndDate = new Date(shift.end);
        let shiftStart = shiftStartDate.getHours().toString().padStart(2, '0') + ':' + shiftStartDate.getMinutes().toString().padStart(2, '0');
        let shiftEnd = shiftEndDate.getHours().toString().padStart(2, '0') + ':' + shiftEndDate.getMinutes().toString().padStart(2, '0');

        //check Same day
        if (shiftStartDate.toDateString() !== shiftEndDate.toDateString()) {
            detail += ` ${shiftStartDate.toLocaleDateString()} ${shiftStart} - ${shiftEndDate.toLocaleDateString()} ${shiftEnd}`;
        } else {
            detail += ` ${shiftStartDate.toLocaleDateString()} ${shiftStart} - ${shiftEnd}`;
        }

        return detail;
    });

    return tooltip + '<br>' + collisionDetails.join('<br>');
};

const createOnDropElementAndSave = (user, craft, shiftQualificationId, isOverbooked = false) => {

    let userType = 0;
    if (user.type === 'freelancer') {
        userType = 1;
    } else if (user.type === 'service_provider') {
        userType = 2;
    } else {
        userType =0;
    }


    droppedUser.value = {
        id: user.id,
        type: userType,
        craft_ids: user.assigned_craft_ids,
        shift_qualifications: user.shift_qualifications ?? [],
        craft_universally_applicable: craft?.universally_applicable ?? false,
        craft_abbreviation: craft.abbreviation ?? '',
    };

    if(!canPlanShifts.value) {
        toastTitle.value = t('You do not have permission to assign users to shifts.');
        toastDescription.value = '';
        toastType.value = 'danger';
        toastVisible.value = true;
        return;
    }
    assignUser(droppedUser, shiftQualificationId, user, isOverbooked);
}

const assignUser = (droppedUser, shiftQualificationId, sourceUser = null, isOverbooked = false) => {

    // Optimistisch Zähler sofort erhöhen (vor dem Request)
    const deltas = isOverbooked ? overbookedQualificationDeltas : shiftQualificationDeltas
    deltas.value = {
        ...deltas.value,
        [shiftQualificationId]: (deltas.value[shiftQualificationId] ?? 0) + 1
    }
    if (sourceUser) {
        adjustDeltaForUser(sourceUser, +1)
    }

    axios.post(
        route('shift.assignUserByType', {shift: props.shift.id}),
        {
            userId: droppedUser.value.id,
            userType: droppedUser.value.type,
            shiftQualificationId: shiftQualificationId,
            seriesShiftData: seriesShiftData.value,
            craft_abbreviation: droppedUser.value.craft_abbreviation,
            isOverbooked: isOverbooked
        },
    ).catch(() => {
        // Bei Fehler: Delta zurücknehmen
        deltas.value = {
            ...deltas.value,
            [shiftQualificationId]: (deltas.value[shiftQualificationId] ?? 0) - 1
        }
        if (sourceUser) {
            adjustDeltaForUser(sourceUser, -1)
        }
    })
}

const AddShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/AddShiftModal.vue'),
    delay: 200,
})

// Öffnet das Bestätigungsmodal zum Löschen der Schicht
const openConfirmDeleteModal = () => {
    showConfirmDeleteModal.value = true;
}

// Handler für Confirm/Cancel aus ConfirmationComponent
const handleConfirmDelete = (confirmed) => {
    showConfirmDeleteModal.value = false;
    if (!confirmed) return;
    router.delete(route('shifts.destroy', { shift: props.shift.id }), {
        preserveScroll: true,
    });
}

// Funktion, um Kollisionen für alle Qualifikationen mit leeren Slots zu prüfen
const checkAllShiftCollisions = (forceRefresh = false) => {
    // Validate that the shift has the minimum required properties before checking for collisions
    if (!props.shift || !props.shift.shifts_qualifications || !props.shift.start || !props.shift.end) {
        return;
    }

    // Check if there are any empty slots to check
    if (computedShiftQualificationDropElements.value.length === 0) {
        return;
    }

    // Für jede Qualifikation mit leeren Slots eine Kollisionsprüfung durchführen
    computedShiftQualificationDropElements.value.forEach(drop => {
        checkShiftCollision(drop.shift_qualification_id, forceRefresh);
    });
};

// Kollisionsprüfung wird lazy beim Öffnen des Zuweisungs-Dropdowns ausgelöst (MenuButton @click).
// Kein onMounted-Check mehr — bei vielen Schichten verursachte das N parallele API-Requests.


// Resolve shiftGroup from lookup — Payloads liefern je nach Ansicht camelCase (ShiftDTO)
// oder snake_case (ShiftListViewSerializer)
const shiftGroupResolved = computed(() =>
    props.shift.shiftGroup
    ?? props.shift.shift_group
    ?? resolveShiftGroup(props.shift.shiftGroupId ?? props.shift.shift_group_id));

// Computed property to get full craft data with color from props.crafts
const fullCraft = computed(() => {
    const shiftCraftId = props.shift?.craft?.id ?? props.shift?.craftId
    if (!shiftCraftId) {
        return props.shift?.craft ?? resolveCraft(props.shift?.craftId) ?? {}
    }

    // Look up craft in props.crafts (can be Array or Object)
    if (props.crafts) {
        const craftsArray = Array.isArray(props.crafts)
            ? props.crafts
            : Object.values(props.crafts || {})
        const foundCraft = craftsArray.find(c => c.id === shiftCraftId)
        if (foundCraft) {
            const baseCraft = props.shift?.craft ?? resolveCraft(shiftCraftId) ?? {}
            return { ...baseCraft, ...foundCraft }
        }
    }

    return props.shift?.craft ?? resolveCraft(shiftCraftId) ?? {}
})

const timePillPadding = computed(() => 'py-1 pr-2 pl-1 text-xs')
// Konsistente Hierarchie wie bei Terminen
const titleTextClass = computed(() => props.hasCollision ? 'text-sm font-semibold' : 'text-base font-semibold')
const subtitleTextClass = computed(() => props.hasCollision ? 'text-xs' : 'text-sm')
const functionBadgeClass = computed(() => props.hasCollision ? 'text-[10px] border-border bg-white' : 'text-xs border-border bg-white')
const craftTitleFull = computed(() => `[${fullCraft.value?.abbreviation}] ${fullCraft.value?.name}`)
const borderColor = computed(() => props.hasCollision ? `${fullCraft.value?.color ?? '#999999'}A0` : 'transparent')

// Wenn sich die Schichtdaten ändern, Cache zurücksetzen.
// Kollisionen werden lazy beim nächsten Dropdown-Open geprüft.
watch(() => props.shift, () => {
    assignablePeopleCache.value = {};
    globalQualificationDeltas.value = {}
    shiftQualificationDeltas.value = {}
    // Auch das Überbuchungs-Delta zurücksetzen: nach dem Broadcast ist der Worker
    // bereits in props.shift enthalten — ohne Reset wurde er doppelt gezählt und
    // offene Überbuchungsplätze verschwanden bis zum Reload.
    overbookedQualificationDeltas.value = {}
}, { deep: true });

// Event-Handler: Wenn Kind-Komponente meldet, dass ein User entfernt wurde, Zähler sofort dekrementieren
const onChildUserRemoved = (payload) => {
    const person = payload?.person
    if (!person) return
    // Shift-Qualifikation-Delta optimistisch dekrementieren
    const sqId = person?.pivot?.shift_qualification_id
    if (sqId != null) {
        shiftQualificationDeltas.value[sqId] = (shiftQualificationDeltas.value[sqId] ?? 0) - 1
    }
    adjustDeltaForUser(person, -1)
}
</script>
