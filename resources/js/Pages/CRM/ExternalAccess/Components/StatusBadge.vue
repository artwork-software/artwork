<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', classes]">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    status: { type: String, required: true },
})

const map = {
    active: { label: 'Active', classes: 'bg-success-surface text-success' },
    expired: { label: 'Expired', classes: 'bg-surface-sunken text-text-muted' },
    revoked: { label: 'Revoked', classes: 'bg-danger-surface text-danger' },
    pending: { label: 'pending', classes: 'bg-warning-surface text-warning' },
    approved: { label: 'approved', classes: 'bg-success-surface text-success' },
    rejected: { label: 'rejected', classes: 'bg-danger-surface text-danger' },
    partially_approved: { label: 'partially_approved', classes: 'bg-warning-surface text-warning' },
    superseded: { label: 'superseded', classes: 'bg-surface-sunken text-text-subtle' },
}

const entry = computed(() => map[props.status] ?? { label: props.status, classes: 'bg-surface-sunken text-text-muted' })
const classes = computed(() => entry.value.classes)
const label = computed(() => $t(entry.value.label))
</script>
