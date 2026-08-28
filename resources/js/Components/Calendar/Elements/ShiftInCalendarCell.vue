<template>
    <!-- Kompaktkachel unterhalb 80 % Zoom (analog CompactEventInCalendar): bei
         Monatsdichte stehen tausende Schichtkarten gleichzeitig im Renderfenster —
         hier darf nichts Teures rein (kein Menü, keine Tooltip-Komponenten, keine
         Direktiven). Details stehen im nativen title, Klick öffnet das Modal. -->
    <div
        v-if="isCompactView"
        class="w-full flex items-center gap-x-1 rounded-sm border px-1 h-6 select-none overflow-hidden"
        :class="canPlanShifts && !isFollowUpDay ? 'cursor-pointer' : ''"
        :style="{ backgroundColor: `${craftColor}${isFollowUpDay ? '30' : '50'}`, borderColor: isFollowUpDay ? '#d1d5db' : craftColor }"
        :title="compactTitle"
        @click="onCompactClick"
    >
        <span class="text-[10px] font-semibold shrink-0">{{ craftAbbreviation }}</span>
        <span class="text-[10px] tabular-nums shrink-0"><span v-if="dayRole === 'end' || dayRole === 'middle'" class="opacity-60">→</span>{{ displayStartTime }}-{{ displayEndTime }}<span v-if="dayRole === 'start' || dayRole === 'middle'" class="opacity-60">→</span></span>
        <span
            class="text-[10px] tabular-nums shrink-0"
            :class="compactAssignedTotal > compactDemandedTotal ? 'text-warning font-semibold' : 'text-text-muted'"
        >{{ compactAssignedTotal }}/{{ compactDemandedTotal }}</span>
        <span v-if="shift.projectName" class="text-[10px] truncate text-text-muted">{{ shift.projectName }}</span>
    </div>

    <!-- Schicht-Karte im Kalender: Optik wie der obere Teil von SingleShiftInDailyShiftView,
         ohne Zuweisungen/Funktions-Verwaltung. Bearbeiten nur über das 3-Punkte-Menü. -->
    <div
        v-else
        class="w-full rounded-sm select-none border font-lexend"
        :style="{ backgroundColor: `${craftColor}${isFollowUpDay ? '30' : '50'}`, borderColor: isFollowUpDay ? '#d1d5db' : craftColor, zoom: contentZoom }"
    >
        <div class="flex flex-col w-full">
            <!-- Zeile 1: Gewerkkürzel + Zeit-Pill + Menü -->
            <div class="flex items-center min-w-0 justify-between">
                <div class="flex items-center min-w-0">
                    <div
                        class="flex items-center gap-x-1.5 rounded-sm whitespace-nowrap px-1.5 py-0.5 text-[11px] font-semibold"
                        :style="{ backgroundColor: `${craftColor}90` }"
                        v-tooltip.bottom="{ value: craftTitleFull, class: 'aw-tooltip' }"
                        :aria-label="craftTitleFull"
                    >
                        <span class="text-text">{{ craftAbbreviation }}</span>
                        <span class="tabular-nums"><span v-if="dayRole === 'end' || dayRole === 'middle'" class="opacity-60">→ </span>{{ displayStartTime }} - {{ displayEndTime }}<span v-if="dayRole === 'start' || dayRole === 'middle'" class="opacity-60"> →</span></span>
                    </div>
                </div>
                <div v-if="!isFollowUpDay && canPlanShifts" class="flex items-center shrink-0 pr-1">
                    <BaseMenu has-no-offset white-menu-background class="cursor-pointer">
                        <BaseMenuItem white-menu-background @click="showAddShiftModal = true" :icon="IconEdit" title="edit" />
                    </BaseMenu>
                </div>
            </div>

            <!-- Zeile 2: Projektname (klickbar → Projekt-Tab mit Schichtenkomponente bzw. Fallback-Tab) -->
            <div v-if="shift.projectName" class="ml-2 min-w-0">
                <Link
                    v-if="shift.projectId && projectShiftTabId"
                    :href="route('projects.tab', { project: shift.projectId, projectTab: projectShiftTabId })"
                    class="block truncate text-xs font-medium text-text hover:underline underline-offset-2 cursor-pointer"
                    v-tooltip.bottom="{ value: shift.projectName, class: 'aw-tooltip' }"
                    @click.stop
                >
                    {{ shift.projectName }}
                </Link>
                <span
                    v-else
                    class="block truncate text-xs text-text-muted"
                    v-tooltip.bottom="{ value: shift.projectName, class: 'aw-tooltip' }"
                >
                    {{ shift.projectName }}
                </span>
            </div>

            <!-- Zeile 2: Auslastung pro Funktion + globale Qualifikationen -->
            <div class="flex justify-between flex-wrap items-center gap-1 ml-2">
                <div class="flex gap-x-2">
                    <div v-for="qualification in shift.shifts_qualifications" :key="qualification.shift_qualification_id">
                        <div class="text-text-muted text-[11px] tabular-nums flex items-center gap-x-1">
                            <div :class="{ 'text-warning font-semibold': getAssignedCountForQualification(qualification.shift_qualification_id) > (qualification.value ?? 0) }">
                                {{ getAssignedCountForQualification(qualification.shift_qualification_id) }}/{{ qualification.value ? qualification.value : '0' }}
                            </div>
                            <ToolTipComponent
                                :icon="findShiftQualification(qualification.shift_qualification_id)?.icon"
                                :tooltip-text="findShiftQualification(qualification.shift_qualification_id)?.name || ''"
                                icon-size="size-4"
                                :stroke="1.75"
                                black-icon
                                classes-button=""
                            />
                        </div>
                    </div>
                </div>

                <div class="flex gap-x-2 pr-4">
                    <div v-for="gq in demandedGlobalQualifications" :key="'gq-' + gq.id">
                        <div class="text-text-muted text-[11px] tabular-nums flex items-center gap-x-1">
                            <div>
                                {{ countAssignedForGlobalQualification(gq.id) }}/{{ getGlobalQuantity(gq) }}
                            </div>
                            <ToolTipComponent
                                :icon="findGlobalQualification(gq.id)?.icon"
                                :tooltip-text="findGlobalQualification(gq.id)?.name || ''"
                                icon-size="size-4"
                                :stroke="1.75"
                                black-icon
                                classes-button=""
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zeile 3: Schichtbeschreibung -->
            <div v-if="shift.description" class="flex items-center gap-x-1 ml-2 mb-1 min-w-0">
                <span
                    class="text-[10px] text-text-muted truncate"
                    v-tooltip.bottom="{ value: shift.description, class: 'aw-tooltip' }"
                >
                    {{ shift.description }}
                </span>
            </div>
            <div v-else class="mb-1"></div>
        </div>
    </div>

    <AddShiftModal
        v-if="showAddShiftModal"
        :crafts="pageCrafts"
        :event="null"
        :shift="shift"
        :currentUserCrafts="usePage().props.currentUserCrafts"
        :buffer="null"
        :shift-qualifications="usePage().props.shiftQualifications"
        :shift-groups="usePage().props.shiftGroups"
        :global-qualifications="usePage().props.globalQualifications"
        :shift-time-presets="usePage().props.shiftTimePresets"
        :shift-plan-modal="true"
        :edit="true"
        :rooms="usePage().props.rooms"
        :room="shift?.roomId ?? null"
        @closed="onShiftModalClosed"
    />
</template>

<script setup>
import {computed, defineAsyncComponent, inject, ref} from "vue";
import { useCalendarZoom } from "@/Composeables/useCalendarZoom.js";
import { useTranslation } from "@/Composeables/Translation.js";
import {Link, usePage} from "@inertiajs/vue3";
import {can, is} from "laravel-permission-to-vuejs";
import {IconEdit} from "@tabler/icons-vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const AddShiftModal = defineAsyncComponent({
    loader: () => import("@/Pages/Projects/Components/AddShiftModal.vue"),
    delay: 200,
});

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
    // Tag der Zelle im Format "dd.mm.yyyy" — bestimmt die Darstellung bei mehrtägigen Schichten
    day: {
        type: String,
        required: true,
    },
    // Tagesansicht positioniert die Karten absolut in Zeitspalten — dort keine Kompaktkachel
    isInDailyView: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["shift-edited"]);

const $t = useTranslation();

const canPlanShifts = computed(() => can("can plan shifts") || is("artwork admin"));

// Über 100 % Kalender-Zoom wächst die Karte per CSS zoom mit (Inhalt layoutet wie
// bei 100 % und wird inkl. Schrift/Icons hochskaliert); bei ≤ 100 % bleibt alles wie bisher.
const { zoomFactor, isCompact } = useCalendarZoom();
const contentZoom = computed(() => (zoomFactor.value > 1 ? zoomFactor.value : 1));
const isCompactView = computed(() => isCompact.value && !props.isInDailyView);

// ----- Kompaktkachel: Summen + title-Tooltip (bewusst ohne Komponenten/Direktiven) -----
// Nenner = NUR shifts_qualifications-Summe (Konvention von Shift::getMaxUsersAttribute):
// globale Qualifikationen sind Querschnittsanforderungen, die von bereits gezählten
// Personen erfüllt werden — sie in den Bedarf zu addieren ließe volle Schichten
// dauerhaft unterbesetzt aussehen.
const compactDemandedTotal = computed(() =>
    (props.shift.shifts_qualifications ?? [])
        .reduce((sum, sq) => sum + (Number(sq?.value) || 0), 0)
);
const compactAssignedTotal = computed(() =>
    Number(props.shift.assignedWorkersTotal ?? (props.shift.workers?.length ?? 0))
);
const compactTitle = computed(() => {
    const parts = [craftTitleFull.value];
    parts.push(`${displayStartTime.value} - ${displayEndTime.value}`);
    parts.push(`${compactAssignedTotal.value}/${compactDemandedTotal.value} ${$t('staffed')}`);
    if (props.shift.projectName) parts.push(props.shift.projectName);
    if (props.shift.description) parts.push(props.shift.description);
    return parts.filter(Boolean).join(' · ');
});
// Im Multi-Edit übernimmt die Zellen-Auswahl den Klick (Wrapper in CalendarDayRow
// reicht ihn dann durch) — die Pille darf dabei kein Schicht-Modal öffnen.
const injectedMultiEdit = inject('calendarMultiEdit', null);

const onCompactClick = () => {
    if (injectedMultiEdit?.value) return;
    if (!canPlanShifts.value || isFollowUpDay.value) return;
    showAddShiftModal.value = true;
};

// Projekt-Tab mit Schichtenkomponente; Backend fällt selbst auf den ersten/Default-Tab
// zurück, wenn keine Schichtenkomponente verbaut ist. Prop-Name je nach Seite unterschiedlich.
const projectShiftTabId = computed(() =>
    usePage().props.first_project_shift_tab_id ?? usePage().props.firstProjectShiftTabId ?? null
);

const showAddShiftModal = ref(false);

const onShiftModalClosed = () => {
    showAddShiftModal.value = false;
    emit("shift-edited", props.shift);
};

const craftColor = computed(() => props.shift?.craft?.color ?? "#999999");
const craftAbbreviation = computed(() => props.shift?.craft?.abbreviation ?? "");
const craftTitleFull = computed(() => {
    const craft = props.shift?.craft;
    if (!craft) return "";
    return craft.abbreviation ? `[${craft.abbreviation}] ${craft.name}` : (craft.name ?? "");
});

// "dd.mm.yyyy" → "yyyy-mm-dd"
const dayIso = computed(() => {
    const parts = String(props.day ?? "").split(".");
    return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : "";
});

const dayRole = computed(() => {
    const startDate = props.shift?.startDate;
    const endDate = props.shift?.endDate;
    if (!startDate || !endDate || startDate === endDate) return "single";
    if (dayIso.value === startDate) return "start";
    if (dayIso.value === endDate) return "end";
    return "middle";
});

const isFollowUpDay = computed(() => dayRole.value === "end" || dayRole.value === "middle");

// Zeiten können als "HH:MM" oder ISO-Datetime ankommen
const normalizeTime = (val) => {
    if (!val || typeof val !== "string") return val;
    if (/^\d{2}:\d{2}$/.test(val)) return val;
    const iso = val.match(/T(\d{2}:\d{2})/);
    if (iso) return iso[1];
    const plain = val.match(/(\d{2}:\d{2})(:\d{2})?$/);
    if (plain) return plain[1];
    return val;
};

const displayStartTime = computed(() => {
    if (dayRole.value === "end" || dayRole.value === "middle") return "00:00";
    return normalizeTime(props.shift.start);
});
const displayEndTime = computed(() => {
    if (dayRole.value === "middle") return "00:00";
    return normalizeTime(props.shift.end);
});

// ----- Qualifikations-Metadaten von der Seite (können als Array oder Map kommen) -----
const toArray = (value) => (Array.isArray(value) ? value : Object.values(value || {}));

const shiftQualificationsMeta = computed(() => toArray(usePage().props.shiftQualifications));
const findShiftQualification = (id) => shiftQualificationsMeta.value.find((q) => q.id === id);

const globalQualificationsMeta = computed(() => toArray(usePage().props.globalQualifications));
const findGlobalQualification = (id) => globalQualificationsMeta.value.find((q) => q.id === id);

const pageCrafts = computed(() => toArray(usePage().props.crafts));

// ----- Auslastung -----
// Bevorzugt die serverseitig aggregierten Zählstände (CalendarShiftDTO);
// Fallback auf die workers-Liste für Payloads, die noch das volle ShiftDTO
// liefern (z.B. Broadcast-Events).
const getAssignedCountForQualification = (id) => {
    const counts = props.shift.assignedCounts;
    if (counts && typeof counts === 'object') return Number(counts[id] ?? 0);
    const workers = props.shift.workers || [];
    return workers.filter((w) => w.pivot?.shift_qualification_id === id).length;
};

const demandedGlobalQualifications = computed(() => {
    const arr = toArray(props.shift?.globalQualifications);
    return arr.filter((gq) => (gq?.pivot?.quantity ?? gq?.quantity ?? 0) > 0);
});

const getGlobalQuantity = (gq) => gq?.pivot?.quantity ?? gq?.quantity ?? 0;

const getPersonGlobalQualificationIds = (person) => {
    const raw = person?.globalQualifications ?? person?.global_qualifications ?? [];
    const arr = Array.isArray(raw) ? raw : Object.values(raw || {});
    return arr
        .map((x) => (typeof x === "number" ? x : x?.id ?? null))
        .filter((v) => typeof v === "number" && Number.isFinite(v));
};

const countAssignedForGlobalQualification = (globalQualificationId) => {
    const counts = props.shift.globalAssignedCounts;
    if (counts && typeof counts === 'object') return Number(counts[globalQualificationId] ?? 0);
    const workers = props.shift?.workers || [];
    return workers.filter((p) => getPersonGlobalQualificationIds(p).includes(globalQualificationId)).length;
};
</script>
