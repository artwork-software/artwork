<template>
    <div class="checklist-listview-container divide-y divide-gray-200 divide-dashed">
        <div v-for="checklist in checklists" :key="checklist.id">
            <SingleChecklistListView
                :checklist="checklist"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="checklist_templates"
                :project="project ?? checklist?.project"
                :tab_id="tab_id"
                :is-in-own-task-management="isInOwnTaskManagement"
            />
        </div>
    </div>

</template>

<script setup>

import SingleChecklistListView from "@/Components/Checklist/Components/SingleChecklistListView.vue";

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
