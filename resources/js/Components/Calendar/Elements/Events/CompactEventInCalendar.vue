<template>
    <div
        class="flex items-center gap-x-1.5 rounded-md border px-1.5 h-6 cursor-pointer select-none overflow-hidden"
        :class="isEmphasized ? 'border-[rgba(0,0,0,0.18)]' : (isDimmed ? 'border-dashed border-border' : 'border-black/10')"
        :style="{
            backgroundColor: eventBgColor,
            color: eventTextColor,
            width: width + 'px',
            minWidth: width + 'px',
            maxWidth: width + 'px',
            // Multi-Edit-Auswahlring (accent + dunkle Außenlinie) und/oder
            // Hervorhebungs-Innenring — kombiniert in containerBoxShadow
            boxShadow: containerBoxShadow
        }"
        @click="onClick"
        @mouseenter="showTooltip"
        @mouseleave="hideTooltip"
    >
        <!-- Status-Punkt für Planungs-/Verifizierungstermine -->
        <span
            v-if="event.isPlanning || event.hasVerification"
            class="size-1.5 rounded-full shrink-0"
            :style="{ backgroundColor: event.hasVerification ? '#f97316' : '#3017AD' }"
        ></span>
        <span class="text-[10px] font-semibold tabular-nums shrink-0">{{ timeLabel }}</span>
        <span class="text-[11px] truncate">{{ titleLabel }}</span>
    </div>

    <!-- Hover-Tooltip mit den vollen Termininfos (ersetzt das frühere Info-Icon) -->
    <Teleport to="body">
        <div
            v-if="tooltipVisible"
            class="fixed z-[9999] pointer-events-none"
            :style="{ top: tooltipPosition.top + 'px', left: tooltipPosition.left + 'px' }"
        >
            <div class="w-64 rounded-lg bg-surface-inverse px-3 py-2 text-white shadow-xl text-xs space-y-0.5">
                <div v-if="event.isPlanning && !event.hasVerification" class="font-semibold text-blue-300">{{ $t('Planned Event') }}</div>
                <div v-else-if="event.hasVerification" class="font-semibold text-special-orange-border">{{ $t('Verification requested') }}</div>
                <div v-if="effectiveEventName" class="font-semibold">{{ effectiveEventName }}</div>
                <div v-if="event.project?.artistNames" class="font-semibold">{{ event.project.artistNames }}</div>
                <div v-if="event.project?.name">{{ $t('Project') }}: {{ event.project.name }}</div>
                <div v-if="event.eventType?.name" class="opacity-80">{{ event.eventType.name }}</div>
                <div class="opacity-80">
                    <template v-if="event.allDay">{{ $t('Full day') }}</template>
                    <template v-else>{{ formattedDates.startTime }} - {{ formattedDates.endTime }}</template>
                    <template v-if="formattedDates.startDate !== formattedDates.endDate">
                        , {{ formattedDates.start_without_year }} - {{ formattedDates.end_without_year }}
                    </template>
                </div>
                <div class="pt-1 text-[10px] opacity-60">{{ $t('Click to open event details') }}</div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, inject, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useColorHelper } from "@/Composeables/UseColorHelper.js";
import { computeEventFormattedDates } from "@/Composeables/calendarDateUtils.js";

// Bewusst schlanke Kompaktkachel für Zoomstufen unter 80 %: bei Monatsdichte
// sind sehr viele Zellen gleichzeitig sichtbar — hier darf nichts Teures rein
// (kein Menü, keine Truncation-Messung, keine Sub-Event-Renderings).

const { t } = useI18n(), $t = t;
const pageProps = usePage().props;
const calSettings = computed(() => pageProps.auth.user.calendar_settings);

const props = defineProps({
    event: { type: Object, required: true },
    width: { type: Number, required: true },
    multiEdit: { type: Boolean, default: false },
});

const emit = defineEmits(["editEvent"]);

const { backgroundColorWithOpacity, getTextColorBasedOnBackground, getHighContrastPercent } = useColorHelper();

// Wie FullEventInCalendar aus den Kalender-Settings ableiten — die frühere
// Page-Prop high_contrast_percent existiert nicht mehr.
const highContrastPercent = computed(() => getHighContrastPercent(calSettings.value));

// Farb-Logik identisch zu FullEventInCalendar (Termintyp-, Status- oder
// Hauptkategorie-Farbe je nach Anzeigeeinstellung)
const baseColor = computed(() => {
    const settings = calSettings.value;
    if (settings.use_main_category_color) {
        if (!props.event?.project) return '#9E9E9E';
        return props.event.project.mainCategoryColor ?? '#3A3A3A';
    }
    if (settings.use_event_status_color) {
        return props.event?.eventStatus?.color ?? props.event?.eventType?.hex_code ?? '#9E9E9E';
    }
    // Termintyp kann gelöscht sein (bekannte Kalender-Audit-Klasse) — Fallback statt TypeError
    return props.event?.eventType?.hex_code ?? '#9E9E9E';
});

// --- Zustandsdarstellung Highlight/Multi-Edit (ersetzt frühere Deckkraft-Dimmung) ---
// HERVORGEHOBEN: Projektzeitraum-Projekt oder highlightEventId-URL-Parameter.
const isEmphasized = computed(() =>
    (!!calSettings.value.time_period_project_id
        && calSettings.value.time_period_project_id === props.event?.project?.id)
    || (!!pageProps.urlParameters?.highlightEventId
        && parseInt(pageProps.urlParameters.highlightEventId) === parseInt(props.event.id))
);

// Ein Highlight-Modus ist aktiv, sobald ein Termin/Projekt hervorgehoben wird —
// alle übrigen Termine gelten dann als "nicht relevant" (gedimmt, ohne opacity).
const highlightModeActive = computed(() =>
    Boolean(pageProps.urlParameters?.highlightEventId)
    || Boolean(calSettings.value.use_project_time_period && calSettings.value.time_period_project_id)
);
const isDimmed = computed(() => !isEmphasized.value && highlightModeActive.value);

// Flächensättigung je Zustand: gedimmt 7 %, hervorgehoben 100 %, sonst Setting (15/75)
const eventBgPercent = computed(() => {
    if (isDimmed.value) return 7;
    if (isEmphasized.value) return 100;
    return highContrastPercent.value;
});

const eventBgColor = computed(() =>
    backgroundColorWithOpacity(baseColor.value, eventBgPercent.value)
);
const eventTextColor = computed(() =>
    isDimmed.value ? '#3F424A' : getTextColorBasedOnBackground(eventBgColor.value)
);

// Multi-Edit-Auswahlring (accent-600 + dunkle Außenlinie) und Hervorhebungs-
// Innenring können gleichzeitig auftreten — daher kombiniert.
const containerBoxShadow = computed(() => {
    const shadows = [];
    if (props.event.considerOnMultiEdit) {
        shadows.push('0 0 0 2px #276293, 0 0 0 3.5px rgba(0,0,0,0.85)');
    }
    if (isEmphasized.value) {
        shadows.push('inset 0 0 0 2px #276293');
    }
    return shadows.length ? shadows.join(', ') : undefined;
});

// Nur die Anfangszeit im Format 00:00; ganztägig = "GT"
const timeLabel = computed(() => {
    if (props.event.allDay) return 'GT';
    const start = String(props.event.start ?? '');
    const match = start.match(/(\d{2}:\d{2})/);
    return match ? match[1] : '';
});

// Viele Termine tragen schlicht den Terminartnamen als Terminnamen ("Probe") —
// der wäre neben der Abkürzung doppelt ("P: Probe") und zählt daher nicht als
// eigener Name.
const effectiveEventName = computed(() => {
    const name = props.event.eventName?.trim();
    if (!name) return null;
    const typeName = props.event.eventType?.name?.trim();
    return typeName && name.toLowerCase() === typeName.toLowerCase() ? null : name;
});

// Dynamischer Titel: Termintitel (bzw. Künstler:innen, wenn eingestellt)
// → Projekttitel → Termintyp. Sobald irgendein Name angezeigt wird, steht die
// Terminart nur als Abkürzung davor; ausgeschrieben erscheint sie erst, wenn
// weder Termin- noch Projektname existieren. Bei aktiver Künstler:innen-
// Anzeigeeinstellung werden die Künstler:innen zusätzlich eingereiht.
const titleLabel = computed(() => {
    const settings = calSettings.value;
    const projectName = props.event.project?.name;
    const artistNames = props.event.project?.artistNames;
    const primary = settings.show_artist_names_as_title && artistNames
        ? artistNames
        : effectiveEventName.value;
    const parts = [];
    if (primary) parts.push(primary);
    if (settings.project_artists && artistNames && artistNames !== primary) parts.push(artistNames);
    if (projectName) parts.push(projectName);
    if (parts.length === 0) return props.event.eventType?.name || '';
    const abbreviation = props.event.eventType?.abbreviation;
    return abbreviation ? `${abbreviation}: ${parts.join(' · ')}` : parts.join(' · ');
});

const formattedDates = computed(() =>
    props.event.formattedDates ?? computeEventFormattedDates(props.event.start, props.event.end)
);

// Multi-Edit als Inject, gelesen NUR im Klick-Handler (nicht im Render):
// so hängt das Rendern der Kachel nicht am Multi-Edit-Zustand und der Toggle
// re-rendert nicht tausende Kompakt-Kacheln. Prop bleibt als Fallback für
// Einbindungen außerhalb von BaseCalendar.
const injectedMultiEdit = inject('calendarMultiEdit', null);

const onClick = () => {
    // Im Multi-Edit übernimmt der Zellen-Wrapper in BaseCalendar die Auswahl
    if (injectedMultiEdit ? injectedMultiEdit.value : props.multiEdit) return;
    emit('editEvent', props.event);
};

const tooltipVisible = ref(false);
const tooltipPosition = ref({ top: 0, left: 0 });

const showTooltip = (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    let left = rect.left;
    let top = rect.bottom + 6;
    if (left + 270 > window.innerWidth) left = window.innerWidth - 280;
    if (top + 180 > window.innerHeight) top = rect.top - 180;
    tooltipPosition.value = { top, left };
    tooltipVisible.value = true;
};

const hideTooltip = () => {
    tooltipVisible.value = false;
};
</script>
