<template>
    <div class="py-10 px-20">
        <div v-if="loadChecklistsError" class="mb-2 text-xs text-rose-600">
            {{ loadChecklistsError }}
        </div>
        <div v-else-if="isLoadingChecklists" class="mb-2 text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <ChecklistFunctionBar
            :project-manager-ids="projectManagerIds"
            :project-can-write-ids="projectCanWriteIds"
            :can-edit-component="canEditComponent"
            :is-admin="isAdmin"
            :project="project"
            :tab_id="tab_id"
            :checklist_templates="localChecklistTemplates"
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

        <div v-if="checklistStyle === 'list'">
            <ChecklistListView
                :checklists="filteredChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="localChecklistTemplates"
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
                :checklist_templates="localChecklistTemplates"
                :project="project"
                :tab_id="tab_id"
            />
        </div>
    </div>
</template>

<script setup>
import {ref, computed, nextTick, watch, onMounted} from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
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
    canEditComponent: Boolean,
    component: Object
});

const {role} = usePermission(usePage().props)

const showSearch = ref(false);
const search = ref('');

const isLoadingChecklists = ref(false);
const loadChecklistsError = ref('');
const localOpenedChecklists = ref(props.opened_checklists ?? []);
const localChecklistTemplates = ref(props.checklist_templates ?? []);
const localPublicChecklists = ref([]);
const localPrivateChecklists = ref([]);

const isAdmin = computed(() => role('artwork admin'));
const currentSort = ref(0)
const checklistStyle = computed(() => {
    const page = usePage();
    return page?.props?.auth?.user?.checklist_style ?? 'list';
});
const projectCanWriteIds = computed(() => {
    let canWriteArray = [];
    if (props.project?.write_auth && Array.isArray(props.project.write_auth)) {
        props.project.write_auth.forEach(write => {
            if (write?.id) {
                canWriteArray.push(write.id);
            }
        });
    }
    return canWriteArray;
});

watch(
    () => [props.project?.id, props.component?.id],
    () => {
        fetchChecklists();
    },
    { immediate: true }
);

async function fetchChecklists() {
    const projectId = props.project?.id;
    const componentInTabId = props.component?.id ?? props.component?.component_in_tab_id;

    if (!projectId || !componentInTabId) {
        return;
    }

    isLoadingChecklists.value = true;
    loadChecklistsError.value = '';

    try {
        const { data } = await axios.get(
            route('projects.tabs.checklists', { project: projectId, componentInTab: componentInTabId })
        );
        localOpenedChecklists.value = data?.opened_checklists ?? [];
        localChecklistTemplates.value = data?.checklist_templates ?? [];
        localPublicChecklists.value = data?.public_checklists ?? [];
        localPrivateChecklists.value = data?.private_checklists ?? [];
    } catch (error) {
        console.error(error);
        loadChecklistsError.value = 'Unable to load checklists.';
    } finally {
        isLoadingChecklists.value = false;
    }
}

const allChecklists = computed(() => {
    const publicLists = Array.isArray(localPublicChecklists.value)
        ? localPublicChecklists.value
        : [];

    const privateLists = Array.isArray(localPrivateChecklists.value)
        ? localPrivateChecklists.value
        : [];

    return publicLists.concat(privateLists);
});


const filteredChecklists = computed(() => {
    const checklists = allChecklists.value.filter(checklist => {
        if (!checklist) return false;
        let include = true;
        if (search.value) {
            const nameMatch = checklist.name?.toLowerCase().includes(search.value.toLowerCase()) ?? false;
            const taskMatch = Array.isArray(checklist.tasks) 
                ? checklist.tasks.some(task => task?.name?.toLowerCase().includes(search.value.toLowerCase()))
                : false;
            include = nameMatch || taskMatch;
        }
        return include;
    });

    if (currentSort.value === 1) {
        return checklists.sort((a, b) => {
            const nameA = a?.name ?? '';
            const nameB = b?.name ?? '';
            return nameA.localeCompare(nameB);
        });
    } else if (currentSort.value === 2) {
        return checklists.sort((a, b) => {
            const nameA = a?.name ?? '';
            const nameB = b?.name ?? '';
            return nameB.localeCompare(nameA);
        });
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
