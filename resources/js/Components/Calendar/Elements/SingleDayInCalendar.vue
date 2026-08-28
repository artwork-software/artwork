<template>
    <div
        :style="containerStyle"
        :class="[isFullscreen ? 'stickyDaysNoMarginLeft' : 'stickyDays', hour ? '!bg-surface-sunken' : '']"
        class=" text-right bg-surface-sunken"
    >
        <!-- Sticky so the date stays visible when scrolling through very tall day rows (expand_days) -->
        <div :style="stickyTop !== null ? { position: 'sticky', top: stickyTop + 'px' } : {}" :class="isCompact ? 'mt-0.5 mr-2' : 'mt-2 mr-2'" v-if="day">
            <!-- Kompakt (33px-Zeilen): Wochentag + Datum in einer Zeile, minimal kleinere Schrift;
                 Feiertagssymbol inline, damit der Feiertag auch hier erkennbar bleibt -->
            <template v-if="isCompact">
                <div class="flex items-center justify-end gap-x-0.5 text-[11px] font-semibold tabular-nums whitespace-nowrap leading-tight">
                    <HolidayToolTip v-if="day?.holidays?.length > 0">
                        <div class="space-y-1 divide-dashed divide-border divide-y">
                            <div v-for="holiday in day.holidays" class="pt-1">
                                <div :style="{ color: holiday.color}">
                                    <div>{{ holiday.name }}</div>
                                    <div v-if="holiday.subdivisions.length > 0">
                                        {{ holiday.subdivisions.map((person) => person).join(', ') }}
                                    </div>
                                    <div v-else>
                                        {{ $t('Germany-wide') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </HolidayToolTip>
                    <span>{{ day.dayString }} {{ day.day }}</span>
                    <span
                        v-if="showRemarkIndicator"
                        class="size-1.5 rounded-full bg-warning shrink-0"
                        :title="remarkIndicatorTitle"
                    ></span>
                </div>
                <div v-if="day.isMonday" class="text-[9px] font-normal tabular-nums leading-tight">(KW{{ day.weekNumber }})</div>
            </template>
            <!-- Normal (ab 80 %): Wochentag klein, Datum größer, Jahr klein daneben -->
            <template v-else>
                <div class="text-[11px] leading-tight">
                    {{ day.dayString }}
                </div>
                <div class="text-sm font-semibold tabular-nums leading-snug whitespace-nowrap">
                    {{ day.day }}<span class="text-[10px] font-normal text-text-subtle ml-0.5">{{ dayYear }}</span>
                </div>
                <div v-if="day.isMonday" class="text-[10px] font-normal tabular-nums">(KW{{ day.weekNumber }})</div>
                <div
                    v-if="showRemarkIndicator"
                    class="flex justify-end mt-0.5"
                    :title="remarkIndicatorTitle"
                >
                    <span class="size-1.5 rounded-full bg-warning"></span>
                </div>
            </template>
            <HolidayToolTip v-if="!isCompact && day?.holidays?.length > 0" class="mt-2">
                <div class="space-y-1 divide-dashed divide-border divide-y">
                    <div v-for="holiday in day.holidays" class="pt-1">
                        <div :style="{ color: holiday.color}">
                            <div>{{ holiday.name }}</div>
                            <div v-if="holiday.subdivisions.length > 0">
                                {{ holiday.subdivisions.map((person) => person).join(', ') }}
                            </div>
                            <div v-else>
                                {{ $t('Germany-wide') }}
                            </div>
                        </div>
                    </div>
                </div>
            </HolidayToolTip>
        </div>
        <div :style="hourTextStyle" class="mt-3 mr-2" v-else>
            <div class="" :class="zoomFactor < 0.6 ? 'text-xs/[15px] text-text' : 'text-sm/5 font-semibold text-text'">
                {{ hour }}
            </div>
        </div>
    </div>
</template>

<script setup>

import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import {useCalendarZoom} from "@/Composeables/useCalendarZoom.js";
import {useDayRemarks} from "@/Composeables/useDayRemarks.js";

const props = defineProps({
    day: {
        type: Object,
        required: false,
        default: null
    },
    isFullscreen: {
        type: Boolean,
        required: false,
        default: false
    },
    hour: {
        type: String,
        required: false,
        default: null
    },
    stickyTop: {
        type: Number,
        required: false,
        default: null
    }
})

const { zoomFactor, rowHeight, dateColumnWidth, isCompact } = useCalendarZoom();

const calendarSettings = computed(() => usePage().props.auth.user.calendar_settings ?? {});

// QOL: Bemerkungs-Indikator, wenn eine Tagesbemerkung existiert, der User die
// Spalte aber über die Anzeigeeinstellungen ausgeblendet hat
const { canView: canViewDayRemarks, columnVisible: dayRemarksColumnVisible, remarkForDay } = useDayRemarks();
const showRemarkIndicator = computed(() =>
    canViewDayRemarks.value
    && !dayRemarksColumnVisible.value
    && !!remarkForDay(props.day)?.text
);
const remarkIndicatorTitle = computed(() => remarkForDay(props.day)?.text ?? null);

const dayYear = computed(() => (props.day?.withoutFormat ?? '').slice(0, 4));

// Wochenend-/Feiertags-Einfärbung auch in der Datumsspalte (gleiche Töne und
// Regeln wie die Tageszeile im Grid: Feiertag schlägt Wochenende, eintägige
// Feiertage deutlich, mehrtägige Zeiträume/Ferien nur sehr dezent, Wochenenden
// im Ferienzeitraum als kräftigere Abstufung derselben Farbe).
const tintColor = computed(() => {
    const day = props.day;
    if (!day || day.isExtraRow) return null;
    const holidays = day.holidays ?? [];
    const singleDayHoliday = holidays.find(
        (holiday) => holiday?.color && (!holiday.end_date || holiday.end_date === holiday.date)
    );
    const coloredHoliday = singleDayHoliday ?? holidays.find((holiday) => holiday?.color);
    if (coloredHoliday) {
        let alpha;
        if (singleDayHoliday) {
            alpha = calendarSettings.value.high_contrast ? '59' : '33';
        } else if (day.isWeekend) {
            alpha = calendarSettings.value.high_contrast ? '66' : '40';
        } else {
            alpha = calendarSettings.value.high_contrast ? '33' : '1A';
        }
        return `${coloredHoliday.color}${alpha}`;
    }
    if (day.isWeekend) {
        return calendarSettings.value.high_contrast ? '#dbeafe' : '#eff6ff';
    }
    return null;
});

const containerStyle = computed(() => {
    // Stunden-Variante (Tagesansicht): Layout bewusst unverändert zum Alt-Stand.
    if (props.hour) {
        return {
            height: calendarSettings.value.expand_days ? '' : zoomFactor.value * 212 + 'px',
            width: zoomFactor.value * 90 + 'px',
            minWidth: zoomFactor.value * 90 + 'px',
        };
    }
    return {
        height: calendarSettings.value.expand_days ? '' : rowHeight.value + 'px',
        width: dateColumnWidth + 'px',
        minWidth: dateColumnWidth + 'px',
        // Tint als Gradient-Layer ÜBER dem deckenden bg-gray-100 statt als
        // backgroundColor: die Tint-Farben sind teils halbtransparent (Ferien
        // 10 % Alpha) — als alleiniger Hintergrund würde die sticky Spalte
        // beim X-Scrollen durchsichtig und die Termine schienen durch.
        ...(tintColor.value ? { backgroundImage: `linear-gradient(${tintColor.value}, ${tintColor.value})` } : {}),
    };
});

// Nur die Stunden-Variante (Tagesansicht) skaliert weiter mit dem Zoom.
const hourTextStyle = computed(() => ({
    fontSize: `max(calc(${zoomFactor.value} * 0.875rem), 10px)`,
    lineHeight: `max(calc(${zoomFactor.value} * 1.25rem), 1.3)`,
}));

</script>

<style scoped>
.stickyDays {
    position: sticky;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}

@media (min-width: 1024px) {
    .stickyDays {
        left: 4rem;
    }
}

.stickyDaysNoMarginLeft {
    position: sticky;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}

</style>
