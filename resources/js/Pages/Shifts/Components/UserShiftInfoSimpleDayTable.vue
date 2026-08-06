<template>
    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-text-subtle">
                <th class="py-2 pr-4 font-medium">{{ $t('Value') }}</th>
                <th class="py-2 px-2 font-medium">{{ granted ? $t('Granted on') : $t('Deadline') }}</th>
                <th class="py-2 pl-2 font-medium">{{ $t('Reason') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border-subtle">
            <tr v-for="row in rows" :key="row.id">
                <td class="py-2 pr-4 font-medium">{{ Number(row.value) === 1 ? $t('Full day') : $t('Half day') }}</td>
                <td class="py-2 px-2">
                    <span :class="!granted && isExpired(row.deadline) ? 'text-danger' : ''">
                        {{ formatDate(granted ? row.granted_date : row.deadline) }}
                    </span>
                    <span v-if="!granted && isExpired(row.deadline)" class="text-xs text-danger">
                        ({{ $t('Deadline expired') }})
                    </span>
                </td>
                <td class="py-2 pl-2 text-text-subtle">{{ row.reason }}</td>
            </tr>
            <tr v-if="!rows || !rows.length">
                <td colspan="3" class="py-4 text-center text-text-subtle">{{ $t(empty) }}</td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
defineProps({
    rows: { type: Array, default: () => [] },
    granted: { type: Boolean, default: false },
    empty: { type: String, default: 'No entries.' },
})

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}

const isExpired = (deadline) => {
    if (!deadline) return false
    return new Date(deadline) < new Date()
}
</script>
