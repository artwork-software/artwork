<template>
    <TrashTab
        :paginator="trashed_events"
        property-name="trashed_events"
        :force-all-href="route('events.force.all')"
        :restore-href="(event) => route('events.restore', { id: event.id })"
        :force-href="(event) => route('events.force', { id: event.id })"
        :name-of="(event) => event.name || event.project?.name || $t('Event without name')"
        :open-href="projectHref"
    >
        <template #leading="{ item: event }">
            <div class="w-1.5 shrink-0 self-stretch rounded-full" :style="{ backgroundColor: event.event_type?.hex_code ?? '#a3a3a3' }" />
        </template>
        <template #default="{ item: event }">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="truncate font-medium text-text">{{ event.name || event.project?.name || $t('Event without name') }}</span>
                <span v-if="event.series_id"
                      class="inline-flex items-center gap-1 rounded-full border border-border-subtle px-2 py-0.5 text-[11px] font-medium text-text-subtle">
                    <IconRepeat class="size-3" />
                    {{ $t('Part of a series') }}
                </span>
            </div>
            <div class="mt-0.5 truncate text-sm text-text-muted">
                <span>{{ event.start }} – {{ event.end }}</span>
                <template v-if="event.event_type?.name">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ event.event_type.name }}</span>
                </template>
                <template v-if="event.room_name">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ event.room_name }}</span>
                </template>
                <template v-if="event.name && event.project">
                    <span class="text-text-subtle"> · </span>
                    <a v-if="projectHref(event)" :href="projectHref(event)" class="text-accent-600 hover:text-accent-700">{{ event.project.name }}</a>
                    <span v-else>{{ event.project.name }}</span>
                </template>
            </div>
        </template>
        <template #menu="{ item: event }">
            <BaseMenuItem v-if="event.series_id" :icon="IconRepeat" title="Restore series" white-menu-background
                          @click="restoreSeries(event.series_id)" />
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
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { IconRepeat } from '@tabler/icons-vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'

const props = defineProps({
    trashed_events: { type: Object, required: true },
    first_project_calendar_tab_id: { type: Number, default: null },
})

// gelöschte Projekte haben keine Seite, auf die der Link zeigen könnte
const projectHref = (event) => event.project?.id && !event.project.deleted_at && props.first_project_calendar_tab_id
    ? route('projects.tab', { project: event.project.id, projectTab: props.first_project_calendar_tab_id })
    : null

const restoreSeries = (seriesId) => {
    // JSON-Endpunkt; danach Liste neu laden
    axios.patch(route('events.series.restore', { series: seriesId }))
        .then(() => router.reload({ only: ['trashed_events'] }))
}
</script>
