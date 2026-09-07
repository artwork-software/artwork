<template>
    <!-- Zelle ohne Bedarf und ohne Schichten: nur ein grauer Strich, nicht klickbar -->
    <div
        v-if="empty"
        class="flex h-full min-h-[64px] items-center justify-center text-text-subtle select-none"
        :title="$t('No shifts')"
    >
        –
    </div>

    <button
        v-else
        type="button"
        class="group/cell flex h-full min-h-[64px] w-full flex-col justify-between rounded-lg border border-transparent px-2 py-1.5 text-left transition hover:border-accent-200 hover:bg-accent-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500"
        :aria-label="ariaLabel"
        @click="$emit('select')"
    >
        <div class="flex items-center justify-between gap-1">
            <span
                :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 whitespace-nowrap', meta.chip]"
            >
                <span :class="['mr-1.5 h-1.5 w-1.5 rounded-full', meta.dot]"></span>
                {{ $t(meta.label) }}
            </span>
            <span
                v-if="deadline"
                :class="['h-2.5 w-2.5 shrink-0 rounded-full', deadline.classes]"
                :title="deadlineTitle"
                :aria-label="deadlineTitle"
            ></span>
        </div>

        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] leading-4 text-text-muted">
            <span class="whitespace-nowrap" :title="$t('Shifts committed')">
                {{ cell.shifts_committed }}/{{ cell.shifts_total }}
            </span>
            <span
                :class="['inline-flex items-center gap-0.5 whitespace-nowrap', cell.open_changes > 0 ? 'text-warning font-medium' : '']"
                :title="$t('Unacknowledged changes')"
            >
                <IconPencil class="h-3 w-3" stroke-width="1.5" />
                {{ cell.open_changes }}
            </span>
            <span
                :class="['inline-flex items-center gap-0.5 whitespace-nowrap', cell.open_violations > 0 ? 'text-danger font-medium' : '']"
                :title="$t('Open rule violations')"
            >
                <IconAlertTriangle class="h-3 w-3" stroke-width="1.5" />
                {{ cell.open_violations }}
            </span>
            <span
                :class="['inline-flex items-center gap-0.5 whitespace-nowrap', cell.open_slots > 0 ? 'text-text font-medium' : '']"
                :title="$t('Open slots / demand')"
            >
                <IconUsers class="h-3 w-3" stroke-width="1.5" />
                {{ cell.open_slots }}/{{ cell.required_slots }}
            </span>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconPencil, IconUsers } from '@tabler/icons-vue'
import { deadlineMeta, isEmptyCell, statusMeta } from '@/Pages/Shifts/WeekStatus/weekStatus.js'

const props = defineProps({
    cell: { type: Object, default: null },
    craftName: { type: String, default: '' },
})

defineEmits(['select'])

const { t } = useI18n()

const empty = computed(() => isEmptyCell(props.cell))
const meta = computed(() => statusMeta(props.cell?.status))
const deadline = computed(() => deadlineMeta(props.cell?.deadline_state))

const deadlineTitle = computed(() => {
    if (!deadline.value) return ''
    const date = props.cell?.deadline_date_formatted
    return date ? `${t(deadline.value.label)} (${date})` : t(deadline.value.label)
})

const ariaLabel = computed(() => {
    if (!props.cell) return ''
    return `${props.craftName} · KW ${props.cell.week_number} / ${props.cell.year}: ${t(meta.value.label)}`
})
</script>
