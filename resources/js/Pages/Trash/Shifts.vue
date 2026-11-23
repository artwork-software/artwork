<template>
    <TrashTab
        :paginator="trashed_shifts"
        property-name="trashed_shifts"
        :force-all-href="route('shifts.trashed.force-all')"
        :restore-href="(shift) => route('shifts.trashed.restore', { shiftId: shift.id })"
        :force-href="(shift) => route('shifts.trashed.force', { shiftId: shift.id })"
        :name-of="(shift) => shift.date + ' ' + shift.time"
        :restore-disabled="(shift) => shift.room_trashed"
    >
        <template #leading="{ item: shift }">
            <div class="w-1.5 shrink-0 self-stretch rounded-full" :style="{ backgroundColor: shift.craft?.color ?? '#a3a3a3' }" />
        </template>
        <template #default="{ item: shift }">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-medium text-text">{{ shift.date }}</span>
                <span class="text-text-muted">{{ shift.time }}</span>
                <span
                    v-if="shift.craft"
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{ backgroundColor: (shift.craft.color ?? '#3730a3') + '15', color: shift.craft.color ?? '#3730a3' }"
                    :title="shift.craft.name"
                >
                    {{ shift.craft.abbreviation || shift.craft.name }}
                </span>
                <span
                    v-if="shift.is_committed"
                    class="inline-flex items-center gap-1 rounded-full border border-border-subtle px-2 py-0.5 text-[11px] font-medium text-text-subtle"
                >
                    <IconLock class="size-3" />
                    {{ $t('Committed') }}
                </span>
            </div>
            <div class="mt-0.5 truncate text-sm text-text-muted">
                <span>{{ shift.room_name ?? $t('No room') }}</span>
                <span v-if="shift.room_trashed" class="text-danger"> ({{ $t('room in recycle bin') }})</span>
                <template v-if="shift.project_name">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ shift.project_name }}</span>
                </template>
                <template v-if="shift.description">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ shift.description }}</span>
                </template>
            </div>
            <div v-if="shift.worker_count > 0" class="mt-0.5 text-xs text-text-subtle">
                {{ $t('{count} assigned persons are restored as well', { count: shift.worker_count }) }}
            </div>
        </template>
    </TrashTab>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import TrashLayout from '@/Layouts/TrashLayout.vue'

export default {
    layout: [AppLayout, TrashLayout],
}
</script>

<script setup>
import TrashTab from '@/Pages/Trash/Components/TrashTab.vue'
import { IconLock } from '@tabler/icons-vue'

defineProps({
    trashed_shifts: { type: Object, required: true },
})
</script>
