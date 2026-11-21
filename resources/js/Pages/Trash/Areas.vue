<template>
    <TrashTab
        :paginator="trashed_areas"
        property-name="trashed_areas"
        :force-all-href="route('areas.force.all')"
        :restore-href="(area) => route('areas.restore', { id: area.id })"
        :force-href="(area) => route('areas.force', { id: area.id })"
        :name-of="(area) => area.name"
        :open-href="() => route('areas.management')"
    >
        <template #default="{ item: area }">
            <div class="truncate font-medium text-text">{{ area.name }}</div>
            <div class="mt-0.5 truncate text-sm text-text-muted" :title="area.room_names.join(', ')">
                <template v-if="area.room_names.length === 0">{{ $t('This area had no rooms.') }}</template>
                <template v-else>{{ $t('Rooms') }}: {{ area.room_names.join(', ') }}</template>
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
    trashed_areas: { type: Object, required: true },
})
</script>
