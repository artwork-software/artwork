<template>
    <div :class="usePage().props.user.checklist_style !== 'list' ? '-mx-5 py-10 px-20 bg-lightBackgroundGray' : 'ml-14 pt-4 pr-14'">
        <ChecklistFunctionBar
            :project-manager-ids="projectManagerIds"
            :project-can-write-ids="projectCanWriteIds"
            :can-edit-component="canEditComponent"
            :is-admin="isAdmin"
            :project="project"
            :tab_id="tab_id"
            :checklist_templates="checklist_templates"
            title="All Checklists"
        >
            <template #search>
                <div v-if="!showSearch" @click="showSearch = true">
                    <IconSearch class="h-6 w-6" />
                </div>
                <div v-if="showSearch">
                    <div class="relative -mt-4">
                        <TextInputComponent
                            id="userSearch"
                            v-model="search"
                            label="Search for tasks"
                            class="w-full"
                            @focus="search = ''"
                            is-small
                        />
                        <div class="absolute right-2 top-2 cursor-pointer" @click="removeSearch">
                            <IconX class="h-6 w-6 text-gray-400" />
                        </div>
                    </div>
                </div>
            </template>
        </ChecklistFunctionBar>

        <div v-if="usePage().props.user.checklist_style === 'list'">
            <ChecklistListView
                :checklists="filteredChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="checklist_templates"
                :project="project"
                :tab_id="tab_id"
            />
        </div>
        <div v-else>
            <ChecklistKanbanView
                :checklists="filteredChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="checklist_templates"
                :project="project"
                :tab_id="tab_id"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import { IconSearch, IconX } from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

import {usePermission} from "@/Composeables/Permission.js";


const props = defineProps({
    project: Object,
    opened_checklists: Array,
    checklist_templates: Array,
    projectManagerIds: Array,
    tab_id: Number,
    canEditComponent: Boolean
});

const { $page } = usePage().props;
const {role} = usePermission(usePage().props)

const showSearch = ref(false);
const search = ref('');

const isAdmin = computed(() => role('artwork admin'));

const projectCanWriteIds = computed(() => {
    let canWriteArray = [];
    props.project.write_auth?.forEach(write => {
        canWriteArray.push(write.id);
    });
    return canWriteArray;
});

const allChecklists = computed(() => {
    return props.project.public_all_checklists.concat(props.project.private_all_checklists);
});


const filteredChecklists = computed(() => {
    return allChecklists.value.filter(checklist => {
        let include = true;
        if (search.value) {
            include = checklist.name.toLowerCase().includes(search.value.toLowerCase()) || checklist.tasks.some(task => task.name.toLowerCase().includes(search.value.toLowerCase()));
        }
        return include;
    });
});

const checklistsComputed = computed(() => {
    return filteredChecklists.value.map(checklist => {
        let tasks = checklist.tasks;
        return {
            ...checklist,
            tasks
        };
    });
});


/*const checklistsComputed = computed(() => {
    // packe alle Task in den Checklisten die erledigt sind nach unten und sortiere die tasks nach deren Deadline. Die nächste Deadline soll oben stehen und füge die Suche für Checklist (name) und task name hinzu
    return allChecklists.value.map(checklist => {
        const tasks = checklist.tasks;

        const completedTasks = tasks.filter(task => task.done);
        const uncompletedTasks = tasks.filter(task => !task.done);

        return {
            ...checklist,
            tasks: [...uncompletedTasks, ...completedTasks]
        };
    });

});*/

const removeSearch = () => {
    search.value = '';
    showSearch.value = false;
};


// add search for checklist name and task name
/*const filteredChecklists = computed(() => {
    return checklistsComputed.value.map(checklist => {
        const tasks = checklist.tasks.filter(task => {
            return task.name.toLowerCase().includes(search.value.toLowerCase());
        });

        const completedTasks = tasks.filter(task => task.done);
        const uncompletedTasks = tasks.filter(task => !task.done);

        return {
            ...checklist,
            tasks: [...uncompletedTasks, ...completedTasks]
        };
    });
});*/

</script>

<style scoped>
</style>
