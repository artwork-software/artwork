<template>
    <div class="checklist-listview-head-bar">
        <div class="head-bar-grid">
            <div class="col-span-6">{{ $t('Task')}}</div>
            <div class="col-span-3 col-start-7">{{ $t('To be done until?')}}</div>
            <div class="col-span-3 col-start-10">{{ $t('Who is responsible for the task?')}}</div>
        </div>
    </div>

    <div class="checklist-listview-container">
        <div v-for="checklist in checklists" v-show="isInOwnTaskManagement ? checklist.tasks.length > 0 : true">
            <SingleChecklistListView
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