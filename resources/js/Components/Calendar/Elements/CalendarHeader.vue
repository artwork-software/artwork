<template>
    <header
        class="sticky top-[71px] z-30 rounded-lg bg-artwork-navigation-background flex items-center gap-0.5 h-16"
        :style="{
      '--col-w': zoomColWidth + 'px',
      '--lead-w': leadWidth + 'px'
    }"
        role="row"
    >
        <!-- linker Spacer -->
        <div class="lead shrink-0" aria-hidden="true"></div>

        <!-- Räume: direkt die Komponente iterieren (kein zusätzlicher Wrapper) -->
        <AsyncSingleRoomInHeader
            v-for="room in rooms"
            :key="room.id ?? room.roomId"
            :room="room"
            is-light
            class="room-col mt-1 line-clamp-2 text-white"
            role="columnheader"
        />
    </header>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import { defineAsyncComponent, computed, ref } from 'vue'

const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1)

const props = defineProps({
    rooms: {
        type: Array, // war: Object
        required: true
    },
    filteredEventsLength: {
        type: Number,
        default: 0
    }
})

/** Breiten nur EINMAL berechnen und als CSS-Variablen durchreichen */
const zoomColWidth = computed(() => Math.round(zoom_factor.value * 212))
const leadWidth    = computed(() =>
    zoom_factor.value === 0.2 ? 50 : Math.round(zoom_factor.value * 90)
)

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
