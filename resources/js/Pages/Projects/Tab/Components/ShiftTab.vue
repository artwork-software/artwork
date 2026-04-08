<template>
    <div class="pt-2 pr-2">
        <div v-if="ready && hasNoEvents" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
            <p class="text-sm">{{ $t('There are no events and shifts for this project yet.') }}</p>
        </div>
        <!-- Wrapper rendert ausschließlich die Daily-View des Schichtplans im Projektkontext -->
        <ShiftPlanDailyView
            v-else-if="ready"
            :project="projectLite"
            :date-value="dateRange"
            :sticky-offset-top-px="stickyOffset"
            :is-in-project-view="true"
        />
        <div v-else class="text-secondary text-sm">{{ $t('Loading...') }}</div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import ShiftPlanDailyView from '@/Pages/Shifts/ShiftPlanDailyView.vue'
import dayjs from 'dayjs'

defineOptions({ name: 'ShiftTab' })

const props = defineProps({
    loadedProjectInformation: { type: Object, required: false, default: () => ({}) },
    headerObject: { type: Object, required: true },
    canEditComponent: { type: Boolean, required: false, default: true },
})

// Nur minimale Projektdaten an die Daily-View weitergeben (Performance)
// Benötigt für Vorauswahl in Modalen: id, name
const projectLite = computed(() => {
    const p = props.headerObject?.project
    if (!p) return null
    return { id: p.id, name: p.name }
})

// Projekt hat weder Termine noch Schichten
const hasNoEvents = computed(() => {
    return !props.headerObject?.firstEventInProject && !props.headerObject?.lastEventInProject
})

// Datumsbereich für den Schicht-Tab strikt am Projektzeitraum ausrichten
// -> firstEventInProject.start_time bis lastEventInProject.end_time
const dateRange = computed(() => {
    const first = props.headerObject?.firstEventInProject?.start_time
    const last = props.headerObject?.lastEventInProject?.end_time

    const toYMD = (val) => {
        const d = dayjs(val)
        return d.isValid() ? d.format('YYYY-MM-DD') : null
    }

    const start = toYMD(first) || usePage().props.dateValue?.[0] || null
    const end = toYMD(last) || usePage().props.dateValue?.[1] || null
    return [start, end]
})

// Verwende die gespeicherte Einstellung des Benutzers
const ready = ref(false)

// Dynamische Sticky-Offset basierend auf der ProjectHeader-Höhe
const stickyOffset = ref(130)

const updateStickyOffset = () => {
    // Die CSS-Variable wird im ProjectHeaderComponent auf einem inneren div gesetzt,
    // daher suchen wir das Element, das die Variable definiert
    const el = document.querySelector('[style*="--project-header-height"]')
    if (el) {
        const headerHeight = getComputedStyle(el).getPropertyValue('--project-header-height')
        if (headerHeight) {
            stickyOffset.value = parseInt(headerHeight, 10) || 130
        }
    }
}

onMounted(() => {
    ready.value = true
    updateStickyOffset()
    window.addEventListener('resize', updateStickyOffset)
})

onUnmounted(() => {
    window.removeEventListener('resize', updateStickyOffset)
})
</script>

<style scoped>
</style>
