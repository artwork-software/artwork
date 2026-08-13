<template>
    <header
        class="sticky z-30 bg-surface-inverse flex items-center gap-0.5 h-11"
        :style="{'--col-w': columnWidth + 'px','--lead-w': dateColumnWidth + 'px','--remarks-w': dayRemarkColumnWidth + 'px', top: stickyTop + 'px'}"
        role="row">
        <!-- linker Spacer: sticky wie die Datumsspalte darunter, sonst schieben
             sich beim horizontalen Scrollen Raumheader über die Datumsspalte -->
        <div
            class="lead shrink-0 h-full bg-surface-inverse sticky-left-lead"
            :class="{ 'no-nav-offset': isFullscreen }"
            aria-hidden="true"
        ></div>

        <!-- Tagesbemerkungen-Spaltenkopf: sticky synchron zur DayRemarkCell
             (Breite + Offset), deckender Hintergrund lässt Raumheader darunter
             durchtauchen -->
        <div
            v-if="showDayRemarksColumn"
            class="remarks-col shrink-0 h-full text-white text-xs font-medium px-1.5 truncate bg-surface-inverse sticky-left-remarks"
            :class="{ 'no-nav-offset': isFullscreen }"
            role="columnheader"
        >
            {{ $t('Day remarks') }}
        </div>

        <!-- Räume: direkt die Komponente iterieren (kein zusätzlicher Wrapper) -->
        <AsyncSingleRoomInHeader
            v-for="room in rooms"
            :key="room.id ?? room.roomId"
            :room="room"
            is-light
            class="room-col text-white"
            role="columnheader"
        />
    </header>
</template>

<script setup>
import { defineAsyncComponent } from 'vue'
import { useCalendarZoom } from '@/Composeables/useCalendarZoom.js'
import { DAY_REMARK_COLUMN_WIDTH } from '@/Composeables/useDayRemarks.js'

// Spaltenbreite ist vom Zoom entkoppelt (Anzeigeeinstellung); die Schrift im
// Raumheader bleibt bei jedem Zoom gleich groß.
const { columnWidth, dateColumnWidth } = useCalendarZoom()

const dayRemarkColumnWidth = DAY_REMARK_COLUMN_WIDTH

const props = defineProps({
    rooms: {
        type: Array,
        required: true
    },
    filteredEventsLength: {
        type: Number,
        default: 0
    },
    stickyTop: {
        type: Number,
        default: 71
    },
    showDayRemarksColumn: {
        type: Boolean,
        default: false
    },
    isFullscreen: {
        type: Boolean,
        default: false
    }
})

const AsyncSingleRoomInHeader = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/Elements/SingleRoomInHeader.vue'),
    suspensible: false,
    delay: 0
})
</script>

<style scoped>
/* nutzt die CSS-Variablen vom Wrapper */
.lead { min-width: var(--lead-w); }

.remarks-col {
    min-width: var(--remarks-w);
    max-width: var(--remarks-w);
    width: var(--remarks-w);
    display: flex;
    align-items: center;
}

.room-col {
    min-width: var(--col-w);
    max-width: var(--col-w);
    width: var(--col-w);
    display: flex;
    align-items: center;
}

/* Horizontale Sticky-Offsets synchron zu SingleDayInCalendar.stickyDays bzw.
   DayRemarkCell.stickyRemarks (90px Datumsspalte + 2px Spalten-Gap; ab lg
   zusätzlich 4rem Nav-Offset, außer im Fullscreen) */
.sticky-left-lead {
    position: sticky;
    position: -webkit-sticky;
    left: 0;
    z-index: 2;
}

.sticky-left-remarks {
    position: sticky;
    position: -webkit-sticky;
    left: 92px;
    z-index: 2;
}

@media (min-width: 1024px) {
    .sticky-left-lead:not(.no-nav-offset) {
        left: 4rem;
    }

    .sticky-left-remarks:not(.no-nav-offset) {
        left: calc(4rem + 92px);
    }
}
</style>
