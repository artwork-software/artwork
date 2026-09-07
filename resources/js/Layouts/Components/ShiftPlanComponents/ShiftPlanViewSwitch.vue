<template>
    <!-- Segment-Control „Woche | Tag | Liste" für die Dienstplan-Funktionsleisten.
         Woche↔Tag laufen über user.update.daily_view (context shift_plan) + Reload,
         Liste ist eine eigene Seite (shifts.plan.list-view). -->
    <div
        class="inline-flex items-center rounded-md border border-border bg-surface p-0.5 select-none"
        role="group"
        :aria-label="$t('Shift plan view')"
    >
        <button
            v-for="segment in segments"
            :key="segment.key"
            type="button"
            class="inline-flex items-center gap-x-1.5 rounded-[5px] px-2.5 min-h-7 text-xs font-medium transition-colors duration-150 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600"
            :class="segment.key === current
                ? 'bg-accent-600 text-white shadow-sm cursor-default'
                : 'text-text-muted hover:bg-surface-sunken hover:text-text cursor-pointer'"
            :aria-pressed="segment.key === current"
            :disabled="switching"
            v-tooltip.bottom="{ value: segment.tooltip, class: 'aw-tooltip' }"
            @click="switchTo(segment.key)"
        >
            <PropertyIcon :name="segment.icon" class="size-4" stroke-width="1.5" aria-hidden="true" />
            <span>{{ segment.label }}</span>
        </button>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({ name: 'ShiftPlanViewSwitch' })

const props = defineProps({
    /** 'week' | 'day' | 'list' */
    current: { type: String, required: true },
})

const { t } = useI18n()
const switching = ref(false)

const segments = computed(() => [
    {
        key: 'week',
        icon: 'IconCalendarWeek',
        label: t('Week'),
        tooltip: t('Week view: rooms and days in a grid, assign by drag & drop'),
    },
    {
        key: 'day',
        icon: 'IconCalendar',
        label: t('Day'),
        tooltip: t('Day view: one day with all shifts and their assignments'),
    },
    {
        key: 'list',
        icon: 'IconList',
        label: t('List'),
        tooltip: t('List view: all shifts of the period as a list'),
    },
])

function patchDailyView(dailyView) {
    return axios.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
        daily_view: dailyView,
        context: 'shift_plan',
    })
}

async function switchTo(target) {
    if (target === props.current || switching.value) return
    switching.value = true

    try {
        if (target === 'list') {
            router.visit(route('shifts.plan.list-view'))
            return
        }

        const dailyView = target === 'day'

        if (props.current === 'list') {
            // Aus der Liste zurück: erst den Modus setzen, dann den Plan laden
            await patchDailyView(dailyView)
            router.visit(route('shifts.plan'))
            return
        }

        router.patch(
            route('user.update.daily_view', usePage().props.auth.user.id),
            { daily_view: dailyView, context: 'shift_plan' },
            { preserveScroll: false, preserveState: false },
        )
    } catch {
        // Modus konnte nicht gespeichert werden — Plan trotzdem öffnen (zeigt den zuletzt gespeicherten Modus)
        router.visit(route('shifts.plan'))
    } finally {
        switching.value = false
    }
}
</script>
