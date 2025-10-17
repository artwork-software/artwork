<template>
    <div class="py-10 px-20">
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
                <div v-if="!showSearch" @click="openSearchBar" class="ui-button">
                    <IconSearch class="h-5 w-5 cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" />
                </div>
                <div v-if="showSearch" class="flex items-center gap-x-2">
                    <div class="relative w-72">
                        <BaseInput
                            id="userSearch"
                            v-model="search"
                            label="Search for to-do lists and to-dos"
                            class="w-full"
                            @focus="search = ''"
                            is-small
                        />
                    </div>

                    <div @click="removeSearch" class="ui-button">
                        <IconX class="h-5 w-5 hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" />
                    </div>
                </div>
            </template>
            <template #sort>
                <BaseMenu show-sort-icon dots-size="h-5 w-5" menu-width="w-72" classes-button="ui-button">
                    <MenuItem v-slot="{ active }">
                        <div @click="currentSort = 1"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                            {{ $t('ToDo-List name descending') }}
                            <IconCheck class="w-5 h-5" v-if="currentSort === 1" />
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <div @click="currentSort = 2"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                            {{ $t('ToDo-List name ascending') }}
                            <IconCheck class="w-5 h-5" v-if="currentSort === 2" />
                        </div>
                    </MenuItem>
                </BaseMenu>
            </template>
        </ChecklistFunctionBar>

        <div v-if="usePage().props.auth.user.checklist_style === 'list'">
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
import {ref, computed, nextTick} from 'vue';
import { usePage } from '@inertiajs/vue3';
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import {IconCheck, IconSearch, IconX} from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

import {usePermission} from "@/Composeables/Permission.js";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";


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
    const publicLists = Array.isArray(props?.project?.public_checklists.data)
        ? props.project.public_checklists.data
        : [];

    const privateLists = Array.isArray(props?.project?.private_checklists.data)
        ? props.project.private_checklists.data
        : [];

    return publicLists.concat(privateLists);
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


const openSearchBar = () => {
    showSearch.value = true;
    nextTick(() => {
        document.getElementById('userSearch').focus();
    });
};


</script>

<style scoped>
</style>
