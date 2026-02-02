<template>
    <div :class="usePage().props.auth.user.checklist_style !== 'list' ? '-mx-5 py-10 px-20 bg-lightBackgroundGray' : 'ml-14 pt-4 pr-14'" class="print:bg-white print:ml-0 print:mr-0 print:pt-0 print:pr-0 print:px-0 print:py-0">
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
            :checklist_templates="checklist_templates"
            title="All Checklists"
        >
            <template #search>
                <div v-if="!showSearch" @click="showSearch = true">
                    <IconSearch class="h-6 w-6" />
                </div>
                <div v-if="showSearch">
                    <div class="relative -mt-4">
                        <BaseInput
                            id="userSearch"
                            v-model="search"
                            label="Search for to-do lists and to-dos"
                            class="w-full"
                            @focus="search = ''"
                            is-small
                        />
                    </div>
                </div>
            </template>
            <template #sort>
                <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
    canEditComponent: Boolean
});

const { $page } = usePage().props;
const {role} = usePermission(usePage().props)

const showSearch = ref(false);
const search = ref('');
const currentSort = ref(0);
const isAdmin = computed(() => role('artwork admin'));

const isLoadingChecklists = ref(false);
const loadChecklistsError = ref('');
const localOpenedChecklists = ref(props.opened_checklists ?? []);
const localPublicAllChecklists = ref([]);
const localPrivateAllChecklists = ref([]);

watch(
    () => props.project?.id,
    () => {
        fetchAllChecklists();
    },
    { immediate: true }
);

async function fetchAllChecklists() {
    const projectId = props.project?.id;

    if (!projectId) {
        return;
    }

    isLoadingChecklists.value = true;
    loadChecklistsError.value = '';

    try {
        const { data } = await axios.get(
            route('projects.tabs.all-checklists', { project: projectId })
        );
        localOpenedChecklists.value = data?.opened_checklists ?? [];
        localPublicAllChecklists.value = data?.public_all_checklists ?? [];
        localPrivateAllChecklists.value = data?.private_all_checklists ?? [];
    } catch (error) {
        console.error(error);
        loadChecklistsError.value = 'Unable to load checklists.';
    } finally {
        isLoadingChecklists.value = false;
    }
}

const projectCanWriteIds = computed(() => {
    let canWriteArray = [];
    props.project.write_auth?.forEach(write => {
        canWriteArray.push(write.id);
    });
    return canWriteArray;
});

const allChecklists = computed(() => {
    const publicLists = Array.isArray(localPublicAllChecklists.value)
        ? localPublicAllChecklists.value
        : [];

    const privateLists = Array.isArray(localPrivateAllChecklists.value)
        ? localPrivateAllChecklists.value
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

// Echo listener for real-time checklist updates
let echoChannel = null;

onMounted(() => {
    if (props.project?.id) {
        echoChannel = Echo.private('project.' + props.project.id)
            .listen('.checklist.updated', () => {
                fetchAllChecklists();
            });
    }
});

onUnmounted(() => {
    if (echoChannel && props.project?.id) {
        Echo.leave('project.' + props.project.id);
    }
});

</script>

<style scoped>
</style>
