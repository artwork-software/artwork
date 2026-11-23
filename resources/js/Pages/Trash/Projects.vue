<template>
    <div class="w-full">
        <TrashTab
            :paginator="trashed_projects"
            property-name="trashed_projects"
            :force-all-href="route('projects.force.all')"
            :restore-href="(project) => route('projects.restore', { id: project.id })"
            :force-href="(project) => route('projects.force', { id: project.id })"
            :name-of="(project) => project.name"
            :open-href="(project) => first_project_tab_id ? route('projects.tab', { project: project.id, projectTab: first_project_tab_id }) : null"
            force-is-queued
        >
            <template #default="{ item: project }">
                <div class="truncate font-medium text-text">{{ project.name }}</div>
                <div v-if="project.project_history?.length" class="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-text-muted">
                    <span>{{ $t('last modified') }}: {{ project.project_history[0]?.created_at }}</span>
                    <button type="button" class="text-accent-600 hover:text-accent-700" @click="historyProjectId = project.id">
                        {{ $t('View history') }}
                    </button>
                </div>
            </template>
            <template #trailing="{ item: project }">
                <div v-if="project.users?.length" class="hidden items-center sm:flex">
                    <div v-for="user in project.users.slice(0, 3)" :key="user.id" class="-mr-2">
                        <UserPopoverTooltip :user="user" :id="user.id" height="7" width="7" />
                    </div>
                    <span v-if="project.users.length > 3" class="ml-3 text-xs text-text-subtle tabular-nums">+{{ project.users.length - 3 }}</span>
                </div>
            </template>
        </TrashTab>

        <ProjectHistoryComponent
            v-if="historyProjectId"
            :project_id="historyProjectId"
            @closed="historyProjectId = null"
        />
    </div>
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
import { ref } from 'vue'
import ProjectHistoryComponent from '@/Layouts/Components/ProjectHistoryComponent.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'

defineProps({
    trashed_projects: { type: Object, required: true },
    first_project_tab_id: { type: Number, default: null },
})

const historyProjectId = ref(null)
</script>
