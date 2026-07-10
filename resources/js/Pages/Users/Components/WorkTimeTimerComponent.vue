<template>
    <div class="w-full max-w-sm text-center font-lexend">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">
            {{ $t('Total Working Time') }}
        </div>
        <div class="flex justify-center items-end gap-x-3 text-4xl font-bold">
            <div class="flex flex-col items-center">
                <span class="text-[10px] text-gray-500 uppercase tracking-wide font-normal mb-0.5">
                    {{ $t('Total actual for period') }}
                </span>
                <span class="text-artwork-buttons-create">{{ formatMinutes(displayWorked) }}</span>
            </div>
            <span class="text-gray-400 text-4xl font-normal pb-1"> - </span>
            <div class="flex flex-col items-center">
                <span class="text-[10px] text-gray-500 uppercase tracking-wide font-normal mb-0.5">
                    {{ $t('Total target for period') }}
                </span>
                <span class="text-gray-800">{{ formatMinutes(displayWanted) }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted, watch} from 'vue'

const props = defineProps({
    totals: {
        type: Object,
        required: true
    }
})

const targetWorked = parseMinutesToSeconds(props.totals.worked)
const targetWanted = parseMinutesToSeconds(props.totals.wanted)

const displayWorked = ref(0)
const displayWanted = ref(0)

const animate = (target, refVar) => {
    const step = Math.ceil(target / 30)
    const interval = setInterval(() => {
        refVar.value = Math.min(refVar.value + step, target)
        if (refVar.value >= target) clearInterval(interval)
    }, 30)
}

onMounted(() => {
    animate(targetWorked, displayWorked)
    animate(targetWanted, displayWanted)
})

function formatMinutes(mins) {
    const h = Math.floor(mins / 60)
    const m = mins % 60
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}

function parseMinutesToSeconds(timeStr) {
    const [h, m] = timeStr.split(':').map(Number)
    return h * 60 + m
}

watch(() => props.totals, (newVal) => {
    const newWorked = parseMinutesToSeconds(newVal.worked)
    const newWanted = parseMinutesToSeconds(newVal.wanted)

    displayWorked.value = 0
    displayWanted.value = 0

    animate(newWorked, displayWorked)
    animate(newWanted, displayWanted)
}, { deep: true })
</script>

