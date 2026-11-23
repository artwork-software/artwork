<template>
    <TrashTab
        :paginator="trashed_rooms"
        property-name="trashed_rooms"
        :force-all-href="route('rooms.force.all')"
        :restore-href="(room) => route('rooms.restore', { id: room.id })"
        :force-href="(room) => route('rooms.force', { id: room.id })"
        :name-of="(room) => room.name"
        :restore-disabled="(room) => room.area_trashed"
        :open-href="() => route('areas.management')"
    >
        <template #default="{ item: room }">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="truncate font-medium text-text">{{ room.name }}</span>
                <span v-if="room.temporary"
                      class="inline-flex items-center rounded-full border border-border-subtle px-2 py-0.5 text-[11px] font-medium text-text-subtle">
                    {{ $t('Temporary room') }}<template v-if="room.start_date">: {{ room.start_date }} – {{ room.end_date }}</template>
                </span>
            </div>
            <div class="mt-0.5 truncate text-sm text-text-muted">
                <span>{{ room.area_name ?? $t('No area') }}</span>
                <span v-if="room.area_trashed" class="text-danger"> ({{ $t('area in recycle bin, restore the area first') }})</span>
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
defineProps({
    trashed_rooms: { type: Object, required: true },
})
</script>
