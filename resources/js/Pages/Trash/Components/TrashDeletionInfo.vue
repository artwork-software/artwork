<template>
    <!-- Wann und von wem gelöscht, dazu die Restfrist bis zur automatischen Löschung -->
    <p v-if="info?.deleted_at" class="mt-0.5 text-xs text-text-subtle">
        <span>
            {{ info.deleted_by
                ? $t('Deleted on {date} by {name}', { date: info.deleted_at, name: info.deleted_by })
                : $t('Deleted on {date}', { date: info.deleted_at }) }}
        </span>
        <template v-if="remaining">
            <span> · </span>
            <span :class="expiringSoon ? 'font-medium text-danger' : ''">{{ $t(remaining.key, remaining.params) }}</span>
        </template>
    </p>
</template>

<script setup>
import { computed } from 'vue'
import { daysLeftText, isExpiringSoon } from '@/Pages/Trash/Composables/trashTools.js'

defineOptions({ name: 'TrashDeletionInfo' })

const props = defineProps({
    // { deleted_at, deleted_by, days_left } aus TrashInfoService
    info: { type: Object, default: null },
})

const remaining = computed(() => daysLeftText(props.info?.days_left))
const expiringSoon = computed(() => isExpiringSoon(props.info?.days_left))
</script>
