<template>
    <div class="w-full max-w-sm text-center font-lexend">
        <div class="text-xs text-text-subtle uppercase tracking-wide mb-1">
            {{ $t('Total Working Time') }}
        </div>
        <div class="flex justify-center items-end gap-x-3 text-4xl font-bold">
            <div class="flex flex-col items-center">
                <span class="text-[10px] text-text-subtle uppercase tracking-wide font-normal mb-0.5">
                    {{ $t('Total actual for period') }}
                </span>
                <span class="text-accent-600">{{ formatMinutes(displayWorked) }}</span>
            </div>
            <span class="text-text-subtle text-4xl font-normal pb-1"> - </span>
            <div class="flex flex-col items-center">
                <span class="text-[10px] text-text-subtle uppercase tracking-wide font-normal mb-0.5">
                    {{ $t('Total target for period') }}
                </span>
                <span v-if="targetUnknown" class="text-text-subtle">–</span>
                <span v-else class="text-text">{{ formatMinutes(displayWanted) }}</span>
            </div>
        </div>
        <div v-if="targetUnknown" class="mt-1 text-[11px] text-warning">
            {{ $t('Work time pattern missing') }}
        </div>
    </div>
</template>

<script setup>
import {computed, ref, onMounted, onBeforeUnmount, watch} from 'vue'

const props = defineProps({
    totals: {
        type: Object,
        required: true
    }
})

// Soll ist null, sobald im Zeitraum an mindestens einem Tag kein Arbeitszeitmuster greift
const targetUnknown = computed(() => props.totals?.wanted_minutes === null || props.totals?.wanted_minutes === undefined)

const displayWorked = ref(0)
const displayWanted = ref(0)
const runningIntervals = new Set()

const toMinutes = (value) => {
    const minutes = Number(value)
    return Number.isFinite(minutes) ? Math.trunc(minutes) : 0
}

// Zählt von 0 auf den Zielwert hoch (auch negative Salden durch Minus-Buchungen)
const animate = (target, refVar) => {
    refVar.value = 0
    if (target === 0) {
        return
    }
    const step = Math.max(1, Math.ceil(Math.abs(target) / 30))
    const direction = Math.sign(target)
    const interval = setInterval(() => {
        const next = refVar.value + direction * step
        refVar.value = direction > 0 ? Math.min(next, target) : Math.max(next, target)
        if (refVar.value === target) {
            clearInterval(interval)
            runningIntervals.delete(interval)
        }
    }, 30)
    runningIntervals.add(interval)
}

const stopAnimations = () => {
    runningIntervals.forEach((interval) => clearInterval(interval))
    runningIntervals.clear()
}

const startAnimations = () => {
    stopAnimations()
    animate(toMinutes(props.totals?.worked_minutes), displayWorked)
    animate(targetUnknown.value ? 0 : toMinutes(props.totals?.wanted_minutes), displayWanted)
}

function formatMinutes(mins) {
    const sign = mins < 0 ? '-' : ''
    const absolute = Math.abs(mins)
    const h = Math.floor(absolute / 60)
    const m = absolute % 60
    return `${sign}${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}

onMounted(startAnimations)
onBeforeUnmount(stopAnimations)

watch(() => props.totals, startAnimations, { deep: true })
</script>
