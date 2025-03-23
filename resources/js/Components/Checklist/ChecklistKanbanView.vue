<template>
    <div class="flex items-start gap-4 overflow-y-scroll" :class="isInOwnTaskManagement ? 'h-[calc(100vh-20rem)]' : ''">
        <div v-for="checklist in checklists">
            <SingleChecklistInKanbanView
                :checklist="checklist"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="checklist_templates"
                :project="project"
                :tab_id="tab_id"
                :is-in-own-task-management="isInOwnTaskManagement"
            />
        </div>
    </div>
</template>

<script setup>

import SingleChecklistInKanbanView from "@/Components/Checklist/Components/SingleChecklistInKanbanView.vue";

const props = defineProps({
    checklists: {
        type: Object,
        required: true
    },
    canEditComponent: {
        type: Boolean,
        required: false,
        default: false
    },
    projectCanWriteIds: {
        type: Array,
        required: false,
        default: []
    },
    projectManagerIds: {
        type: Array,
        required: false,
        default: []
    },
    isAdmin: {
        type: Boolean,
        required: false,
        default: false
    },
    project: {
        type: Object,
        required: false,
        default: []
    },
    checklist_templates: {
        type: Object,
        required: false,
        default: []
    },
    tab_id: {
        type: Number,
        required: false
    },
    isInOwnTaskManagement: {
        type: Boolean,
        required: false,
        default: false
    }
})

</script>

<style scoped>

</style>