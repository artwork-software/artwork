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
    active: { label: 'Active', classes: 'bg-emerald-50 text-emerald-700' },
    expired: { label: 'Expired', classes: 'bg-zinc-100 text-zinc-600' },
    revoked: { label: 'Revoked', classes: 'bg-red-50 text-red-700' },
    pending: { label: 'pending', classes: 'bg-amber-50 text-amber-700' },
    approved: { label: 'approved', classes: 'bg-emerald-50 text-emerald-700' },
    rejected: { label: 'rejected', classes: 'bg-red-50 text-red-700' },
    partially_approved: { label: 'partially_approved', classes: 'bg-amber-50 text-amber-700' },
    superseded: { label: 'superseded', classes: 'bg-zinc-100 text-zinc-500' },
}

const entry = computed(() => map[props.status] ?? { label: props.status, classes: 'bg-zinc-100 text-zinc-600' })
const classes = computed(() => entry.value.classes)
const label = computed(() => $t(entry.value.label))
</script>
