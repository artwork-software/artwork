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
            <template #sort>
                <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                    <MenuItem v-slot="{ active }">
                        <div @click="currentSort = 1"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                            Checkliste Name absteigend
                            <IconCheck class="w-5 h-5" v-if="currentSort === 1" />
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <div @click="currentSort = 2"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                            Checkliste Name aufsteigend
                            <IconCheck class="w-5 h-5" v-if="currentSort === 2" />
                        </div>
                    </MenuItem>
                </BaseMenu>
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
import {IconCheck, IconSearch, IconX} from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

import {usePermission} from "@/Composeables/Permission.js";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";


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
const currentSort = ref(0)
const projectCanWriteIds = computed(() => {
    let canWriteArray = [];
    props.project.write_auth?.forEach(write => {
        canWriteArray.push(write.id);
    });
    return canWriteArray;
});

const allChecklists = computed(() => {
    return props.project.public_checklists.concat(props.project.private_checklists);
});


const filteredChecklists = computed(() => {
    const checklists = allChecklists.value.filter(checklist => {
        let include = true;
        if (search.value) {
            include = checklist.name.toLowerCase().includes(search.value.toLowerCase()) || checklist.tasks.some(task => task.name.toLowerCase().includes(search.value.toLowerCase()));
        }
        return include;
    });

    if (currentSort.value === 1) {
        return checklists.sort((a, b) => a.name.localeCompare(b.name));
    } else if (currentSort.value === 2) {
        return checklists.sort((a, b) => b.name.localeCompare(a.name));
    } else {
        return checklists;
    }
});


const removeSearch = () => {
    search.value = '';
    showSearch.value = false;
};



</script>

<style scoped>
</style>
