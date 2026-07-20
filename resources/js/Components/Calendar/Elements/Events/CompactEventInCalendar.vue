<template>
    <div
        class="flex items-center gap-x-1.5 rounded-md border border-black/10 px-1.5 h-6 cursor-pointer select-none overflow-hidden"
        :style="{
            backgroundColor: eventBgColor,
            color: eventTextColor,
            width: width + 'px',
            minWidth: width + 'px',
            maxWidth: width + 'px'
        }"
        :class="event.considerOnMultiEdit ? 'ring-2 ring-green-500' : ''"
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
            <div class="w-64 rounded-lg bg-artwork-navigation-background px-3 py-2 text-white shadow-xl text-xs space-y-0.5">
                <div v-if="event.isPlanning && !event.hasVerification" class="font-semibold text-blue-300">{{ $t('Planned Event') }}</div>
                <div v-else-if="event.hasVerification" class="font-semibold text-orange-300">{{ $t('Verification requested') }}</div>
                <div v-if="event.eventName" class="font-semibold">{{ event.eventName }}</div>
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
import { computed, ref } from "vue";
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
const highContrastPercent = computed(() => pageProps.high_contrast_percent);

const props = defineProps({
    event: { type: Object, required: true },
    width: { type: Number, required: true },
    multiEdit: { type: Boolean, default: false },
});

const emit = defineEmits(["editEvent"]);

const { backgroundColorWithOpacity, getTextColorBasedOnBackground } = useColorHelper();

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

const eventBgColor = computed(() =>
    backgroundColorWithOpacity(baseColor.value, highContrastPercent.value)
);
const eventTextColor = computed(() => getTextColorBasedOnBackground(eventBgColor.value));

// Nur die Anfangszeit im Format 00:00; ganztägig = "GT"
const timeLabel = computed(() => {
    if (props.event.allDay) return 'GT';
    const start = String(props.event.start ?? '');
    const match = start.match(/(\d{2}:\d{2})/);
    return match ? match[1] : '';
});

// Dynamischer Titel: Termintitel (bzw. Künstler:innen, wenn eingestellt)
// → Projekttitel → Termintyp. Titel + Projekt kombiniert, soweit Platz ist.
const titleLabel = computed(() => {
    const projectName = props.event.project?.name;
    const artistNames = props.event.project?.artistNames;
    const primary = calSettings.value.show_artist_names_as_title && artistNames
        ? artistNames
        : props.event.eventName;
    if (primary && projectName) return `${primary} · ${projectName}`;
    return primary || projectName || props.event.eventType?.name || '';
});

const formattedDates = computed(() =>
    props.event.formattedDates ?? computeEventFormattedDates(props.event.start, props.event.end)
);

const onClick = () => {
    // Im Multi-Edit übernimmt der Zellen-Wrapper in BaseCalendar die Auswahl
    if (props.multiEdit) return;
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
