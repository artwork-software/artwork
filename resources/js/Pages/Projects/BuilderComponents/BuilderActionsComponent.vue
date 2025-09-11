<template>
    <component :is="IconCalendarMonth" class="w-6 h-6 cursor-pointer" @click="useProjectTimePeriodAndRedirect()"/>
    <BaseMenu has-no-offset :button-id="'project-menu-' + project.id">
        <BaseMenuItem
            as-link
            :link="route('projects.tab', { project: project.id, projectTab: project.firstTabId })"
            title="Open"
            :icon="IconFolderOpen"
        />
        <BaseMenuItem title="Edit" />
        <BaseMenuItem title="Delete" icon="IconTrash" />
    </BaseMenu>
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {router} from "@inertiajs/vue3";
import {IconCalendarMonth, IconFolderOpen} from "@tabler/icons-vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    menuVisible: {
        type: Boolean,
        required: true,
    },
    menuPosition: {
        type: Object,
        required: true,
    },
})

const useProjectTimePeriodAndRedirect = () => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
        {
            use_project_time_period: true,
            project_id: props.project.id
        }
    );
}

</script>

<style scoped>

</style>