<template>
    <div
        :style="containerStyle"
        :class="[isFullscreen ? 'stickyDaysNoMarginLeft' : 'stickyDays', hour ? '!bg-gray-100' : '']"
        class="text-calendarText text-right bg-gray-100"
    >
        <!-- Sticky so the date stays visible when scrolling through very tall day rows (expand_days) -->
        <div :style="stickyTop !== null ? { position: 'sticky', top: stickyTop + 'px' } : {}" :class="isCompact ? 'mt-0.5 mr-2' : 'mt-2 mr-2'" v-if="day">
            <!-- Kompakt (33px-Zeilen): Wochentag + Datum in einer Zeile, minimal kleinere Schrift;
                 Feiertagssymbol inline, damit der Feiertag auch hier erkennbar bleibt -->
            <template v-if="isCompact">
                <div class="flex items-center justify-end gap-x-0.5 text-xs font-semibold whitespace-nowrap leading-tight">
                    <HolidayToolTip v-if="day?.holidays?.length > 0">
                        <div class="space-y-1 divide-dashed divide-gray-500 divide-y">
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
                </div>
                <div v-if="day.isMonday" class="text-[9px] font-normal leading-tight">(KW{{ day.weekNumber }})</div>
            </template>
            <!-- Normal (ab 80 %): Wochentag klein, Datum größer, Jahr klein daneben -->
            <template v-else>
                <div class="text-[11px] leading-tight">
                    {{ day.dayString }}
                </div>
                <div class="text-sm font-semibold leading-snug whitespace-nowrap">
                    {{ day.day }}<span class="text-[10px] font-normal text-gray-500 ml-0.5">{{ dayYear }}</span>
                </div>
                <div v-if="day.isMonday" class="text-[10px] font-normal">(KW{{ day.weekNumber }})</div>
            </template>
            <HolidayToolTip v-if="!isCompact && day?.holidays?.length > 0" class="mt-2">
                <div class="space-y-1 divide-dashed divide-gray-500 divide-y">
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
            <div class="" :class="zoomFactor < 0.6 ? 'xxsDark' : 'xsDark'">
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

const dayYear = computed(() => (props.day?.withoutFormat ?? '').slice(0, 4));

// Wochenend-/Feiertags-Einfärbung auch in der Datumsspalte (gleiche Töne und
// Regeln wie die Tageszeile im Grid: Feiertag schlägt Wochenende, eintägige
// Feiertage deutlich, mehrtägige Zeiträume/Ferien nur sehr dezent).
const tintColor = computed(() => {
    const day = props.day;
    if (!day || day.isExtraRow) return null;
    const holidays = day.holidays ?? [];
    const singleDayHoliday = holidays.find(
        (holiday) => holiday?.color && (!holiday.end_date || holiday.end_date === holiday.date)
    );
    const coloredHoliday = singleDayHoliday ?? holidays.find((holiday) => holiday?.color);
    if (coloredHoliday) {
        const alpha = singleDayHoliday
            ? (calendarSettings.value.high_contrast ? '59' : '33')
            : (calendarSettings.value.high_contrast ? '33' : '1A');
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
        ...(tintColor.value ? { backgroundColor: tintColor.value } : {}),
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
