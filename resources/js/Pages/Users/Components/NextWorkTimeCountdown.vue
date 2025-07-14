<template>
    <div v-if="nextWorkTime && nextWorkTime.valid_from" class="mt-6 p-4 border border-gray-200 rounded-lg shadow-sm bg-white">
        <div class="mb-2 text-sm text-gray-700 font-lexend">
            <strong>{{ $t('Next Work Time Pattern') }}</strong><br>
            <span class="text-xs text-gray-500">{{ $t('Starts on') }} {{ dayjs(nextWorkTime.valid_from).format('DD.MM.YYYY') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 text-xs text-gray-600 font-mono">
            <div><b>{{ $t('Monday') }}:</b> {{ formatTime(nextWorkTime.monday) }}</div>
            <div><b>{{ $t('Tuesday') }}:</b> {{ formatTime(nextWorkTime.tuesday) }}</div>
            <div><b>{{ $t('Wednesday') }}:</b> {{ formatTime(nextWorkTime.wednesday) }}</div>
            <div><b>{{ $t('Thursday') }}:</b> {{ formatTime(nextWorkTime.thursday) }}</div>
            <div><b>{{ $t('Friday') }}:</b> {{ formatTime(nextWorkTime.friday) }}</div>
            <div><b>{{ $t('Saturday') }}:</b> {{ formatTime(nextWorkTime.saturday) }}</div>
            <div><b>{{ $t('Sunday') }}:</b> {{ formatTime(nextWorkTime.sunday) }}</div>
        </div>

        <div class="mt-2 text-sm text-gray-800 font-lexend">
            {{ $t('Total weekly hours:') }} <b>{{ nextWorkTime.full_work_time_in_hours }} Std</b>
        </div>

        <div class="mt-2 text-sm text-blue-600">
            <b>{{ $t('Starts in:') }}</b> {{ countdownText }}
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import dayjs from 'dayjs'
import duration from 'dayjs/plugin/duration'

dayjs.extend(duration)

const props = defineProps({
    nextWorkTime: {
        type: Object,
        required: true
    }
})

const countdownText = ref(null)
let interval = null

const updateCountdown = () => {
    const target = dayjs(props.nextWorkTime.valid_from).startOf('day')
    const now = dayjs()

    const diff = target.diff(now)
    if (diff <= 0) {
        countdownText.value = null
        clearInterval(interval)
        return
    }

    const d = dayjs.duration(diff)
    countdownText.value = `${d.days()}d ${d.hours()}h ${d.minutes()}m ${d.seconds()}s`
}

const formatTime = (value) => {
    return value ? value : '00:00'
}

onMounted(() => {
    updateCountdown()
    interval = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
    clearInterval(interval)
})
</script>
