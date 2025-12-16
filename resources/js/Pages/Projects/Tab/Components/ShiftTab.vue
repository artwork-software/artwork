<template>
    <div class="pt-2 pr-2">
        <!-- Wrapper rendert ausschließlich die Daily-View des Schichtplans im Projektkontext -->
        <ShiftPlanDailyView
            v-if="ready"
            :project="projectLite"
            :date-value="dateRange"
            :sticky-offset-top-px="48"
            :is-in-project-view="true"
        />
        <div v-else class="text-secondary text-sm">{{ $t('Loading...') }}</div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
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
onMounted(() => {
    ready.value = true
})
</script>

<style scoped>
</style>
