<template>
    <header
        class="sticky z-30 rounded-lg bg-artwork-navigation-background flex items-center gap-0.5 h-11"
        :style="{'--col-w': columnWidth + 'px','--lead-w': dateColumnWidth + 'px', top: stickyTop + 'px'}"
        role="row">
        <!-- linker Spacer -->
        <div class="lead shrink-0" aria-hidden="true"></div>

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

// Spaltenbreite ist vom Zoom entkoppelt (Anzeigeeinstellung); die Schrift im
// Raumheader bleibt bei jedem Zoom gleich groß.
const { columnWidth, dateColumnWidth } = useCalendarZoom()

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

.room-col {
    min-width: var(--col-w);
    max-width: var(--col-w);
    width: var(--col-w);
    display: flex;
    align-items: center;
}
</style>
