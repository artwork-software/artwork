<template>
    <div class="py-10 px-20">
        <div v-if="!isInOwnTaskManagement && loadChecklistsError" class="mb-2 text-xs text-rose-600">
            {{ loadChecklistsError }}
        </div>
        <div v-else-if="!isInOwnTaskManagement && isLoadingChecklists" class="mb-2 text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>

        <!-- Filter bar for OwnTasksManagement mode -->
        <section v-if="isInOwnTaskManagement" class="rounded-2xl border border-gray-100 bg-white shadow-sm px-5 py-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Search -->
                <div class="relative w-full lg:w-96">
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="$t('Search lists & tasks...')"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/40 transition"
                    />
                    <span v-if="search" class="absolute inset-y-0 right-0 flex items-center pr-2">
                      <button @click="search=''" class="rounded-lg p-1 text-gray-400 hover:text-gray-600">
                        <IconX class="size-4" />
                      </button>
                    </span>
                </div>

                <!-- Chips + Sort + View -->
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-end w-full">
                    <!-- Filter Chips -->
                    <div class="flex flex-col gap-2">
                        <!-- Row 1: Mutually-exclusive groups + Reset -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Project group: with/without project -->
                            <div v-if="showProjectFilter" class="inline-flex items-center gap-1 rounded-xl border border-gray-200 p-1" role="group" :aria-label="$t('Project filter')">
                                <button
                                    @click="toggleFlag('checklist_has_projects')"
                                    :class="groupedChipClass(user.checklist_has_projects)"
                                    class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-emerald-500"></span>{{ $t('with project') }}
                                </button>
                                <button
                                    @click="toggleFlag('checklist_no_projects')"
                                    :class="groupedChipClass(user.checklist_no_projects)"
                                    class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-amber-500"></span>{{ $t('without project') }}
                                </button>
                            </div>

                            <!-- Privacy group: personal/shared -->
                            <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 p-1" role="group" :aria-label="$t('Privacy filter')">
                                <button
                                    @click="toggleFlag('checklist_private_checklists')"
                                    :class="groupedChipClass(user.checklist_private_checklists)"
                                    class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-indigo-500"></span>{{ $t('private') }}
                                </button>
                                <button
                                    @click="toggleFlag('checklist_no_private_checklists')"
                                    :class="groupedChipClass(user.checklist_no_private_checklists)"
                                    class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-pink-500"></span>{{ $t('shared') }}
                                </button>
                            </div>

                            <!-- Reset button -->
                            <button @click="resetFilters" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 4h16M4 12h16M4 20h16"/></svg>
                                {{ $t('Reset') }}
                            </button>
                        </div>

                        <!-- Row 2: Display options -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                @click="toggleFlag('checklist_completed_tasks')"
                                :class="chipClass(user.checklist_completed_tasks)"
                                class="inline-flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs"
                                type="button"
                            >
                                <span class="size-2 rounded-full bg-slate-500"></span>{{ $t('Show completed') }}
                            </button>
                            <button
                                @click="toggleFlag('checklist_show_without_tasks')"
                                :class="chipClass(user.checklist_show_without_tasks)"
                                class="inline-flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs"
                                type="button"
                            >
                                <span class="size-2 rounded-full bg-cyan-500"></span>{{ $t('Show empty lists') }}
                            </button>
                        </div>
                    </div>

                    <!-- Sort + View -->
                    <div class="flex items-center gap-2">
                        <!-- Sort -->
                        <div class="relative">
                            <button @click="sortOpen = !sortOpen" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-xs hover:bg-gray-50">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M3 7h14M3 12h10M3 17h6"/></svg>
                                {{ sortLabel }}
                            </button>
                            <div v-if="sortOpen" @click.outside="sortOpen=false" class="absolute right-0 mt-2 w-64 rounded-xl border border-gray-100 bg-white shadow-lg p-1 z-10">
                                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="sortTo(1)">{{ $t('Project timeframe ascending') }} <IconCheck v-if="currentSort===1" class="inline size-4 ml-1" /></button>
                                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="sortTo(2)">{{ $t('Project timeframe descending') }} <IconCheck v-if="currentSort===2" class="inline size-4 ml-1" /></button>
                                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="currentSort=3; sortOpen=false">{{ $t('List name ascending') }} <IconCheck v-if="currentSort===3" class="inline size-4 ml-1" /></button>
                                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="currentSort=4; sortOpen=false">{{ $t('List name descending') }} <IconCheck v-if="currentSort===4" class="inline size-4 ml-1" /></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save notification -->
            <transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1">
                <div v-show="filterSaved" class="text-xs bg-emerald-600 text-white rounded-lg px-3 py-1.5 mt-5">
                    {{ $t('Filter was saved') }}
                </div>
            </transition>
        </section>

        <!-- Project-context function bar (search + sort only) -->
        <ChecklistFunctionBar
            v-if="!isInOwnTaskManagement"
            :project-manager-ids="projectManagerIds"
            :project-can-write-ids="projectCanWriteIds"
            :can-edit-component="canEditComponent"
            :is-admin="isAdmin"
            :project="project"
            :tab_id="tab_id"
            :checklist_templates="localChecklistTemplates"
            @update:checklist-style="(val) => checklistStyle = val"
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

        <!-- View switch for OwnTasksManagement -->
        <div v-if="isInOwnTaskManagement" class="flex justify-end mb-4">
            <div class="inline-flex rounded-xl border border-gray-200 p-1">
                <button @click="updateChecklistStyleLocal('list')" :class="checklistStyle==='list' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-lg text-xs font-medium">
                    {{ $t('List') }}
                </button>
                <button @click="updateChecklistStyleLocal('kanban')" :class="checklistStyle==='kanban' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-lg text-xs font-medium">
                    {{ $t('Kanban') }}
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="finalChecklists.length === 0 && !isLoadingChecklists" class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center">
            <p class="text-sm text-gray-600">{{ $t('No fitting checklists found') }}</p>
        </div>

        <div v-else-if="checklistStyle === 'list'">
            <ChecklistListView
                :checklists="finalChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="localChecklistTemplates"
                :project="project"
                :tab_id="tab_id"
                :is-in-own-task-management="isInOwnTaskManagement"
            />
        </div>
        <div v-else>
            <ChecklistKanbanView
                :checklists="finalChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="isAdmin"
                :checklist_templates="localChecklistTemplates"
                :project="project"
                :tab_id="tab_id"
                :is-in-own-task-management="isInOwnTaskManagement"
            />
        </div>
    </div>
</template>

<script setup>
import {ref, computed, nextTick, watch, onMounted, onUnmounted} from 'vue';
import { usePage, router } from '@inertiajs/vue3';
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
    component: Object,
    // OwnTasksManagement mode props
    isInOwnTaskManagement: { type: Boolean, default: false },
    externalPublicChecklists: { type: Array, default: () => [] },
    externalPrivateChecklists: { type: Array, default: [] },
    showProjectFilter: { type: Boolean, default: false },
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
const checklistStyle = ref(usePage()?.props?.auth?.user?.checklist_style ?? 'list');
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

// OwnTasksManagement filter state
const page = usePage();
const user = computed(() => page.props.auth.user);
const sortOpen = ref(false);
const filterSaved = ref(false);

const sortLabel = computed(() => {
    switch (currentSort.value) {
        case 1: return 'Projektzeitraum aufsteigend';
        case 2: return 'Projektzeitraum absteigend';
        case 3: return 'Listenname aufsteigend';
        case 4: return 'Listenname absteigend';
        default: return 'Sortieren';
    }
});

// --- Data source ---
// In project context: fetch via axios. In OwnTasksManagement: use props.
if (props.isInOwnTaskManagement) {
    // Initialize from external props
    localPublicChecklists.value = props.externalPublicChecklists ?? [];
    localPrivateChecklists.value = props.externalPrivateChecklists ?? [];
}

watch(
    () => [props.externalPublicChecklists, props.externalPrivateChecklists],
    ([pub, priv]) => {
        if (props.isInOwnTaskManagement) {
            localPublicChecklists.value = pub ?? [];
            localPrivateChecklists.value = priv ?? [];
            syncOpenedChecklists();
        }
    },
    { deep: false }
);

watch(
    () => [props.project?.id, props.component?.id],
    ([projectId, componentId]) => {
        // Only fetch when we have both a valid project and component ID (project context)
        if (!props.isInOwnTaskManagement && projectId && componentId) {
            fetchChecklists();
        }
    },
    { immediate: true }
);

async function fetchChecklists() {
    const projectId = props.project?.id;
    const componentInTabId = props.component?.id ?? props.component?.component_in_tab_id;

    // Only fetch if we have both projectId and a valid componentInTabId
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

        syncOpenedChecklists();
    } catch (error) {
        console.error(error);
        loadChecklistsError.value = 'Unable to load checklists.';
    } finally {
        isLoadingChecklists.value = false;
    }
}

function syncOpenedChecklists() {
    const page = usePage();
    if (page?.props?.auth?.user) {
        const currentOpened = page.props.auth.user.opened_checklists ?? [];
        const allChecklistIds = [...localPublicChecklists.value, ...localPrivateChecklists.value].map(cl => cl.id);
        const missingIds = allChecklistIds.filter(id => !currentOpened.includes(id));
        if (missingIds.length > 0) {
            page.props.auth.user.opened_checklists = [...currentOpened, ...missingIds];
        }
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

        // OwnTasksManagement filters
        if (props.isInOwnTaskManagement) {
            if (user.value.checklist_has_projects) include = include && checklist.hasProject === true;
            if (user.value.checklist_no_projects) include = include && checklist.hasProject === false;
            if (user.value.checklist_private_checklists) include = include && checklist.private === true;
            if (user.value.checklist_no_private_checklists) include = include && checklist.private === false;

            if (user.value.checklist_show_without_tasks) {
                include = include && (checklist.tasks?.length ?? 0) >= 0;
            } else {
                const visible = (checklist.tasks || []).some(t => !t.done);
                include = include && (checklist.tasks?.length ?? 0) > 0 && visible;
            }
        }

        return include;
    });

    if (currentSort.value === 1 || (!props.isInOwnTaskManagement && currentSort.value === 1)) {
        return checklists.sort((a, b) => {
            const nameA = a?.name ?? '';
            const nameB = b?.name ?? '';
            return nameA.localeCompare(nameB);
        });
    } else if (currentSort.value === 2 || (!props.isInOwnTaskManagement && currentSort.value === 2)) {
        return checklists.sort((a, b) => {
            const nameA = a?.name ?? '';
            const nameB = b?.name ?? '';
            return nameB.localeCompare(nameA);
        });
    } else if (currentSort.value === 3) {
        return checklists.sort((a, b) => (a?.name ?? '').localeCompare(b?.name ?? ''));
    } else if (currentSort.value === 4) {
        return checklists.sort((a, b) => (b?.name ?? '').localeCompare(a?.name ?? ''));
    } else {
        return checklists;
    }
});

// For OwnTasksManagement: filter out done tasks based on user preference
const finalChecklists = computed(() => {
    if (!props.isInOwnTaskManagement) return filteredChecklists.value;

    return filteredChecklists.value.map(cl => {
        const tasks = user.value.checklist_completed_tasks
            ? (cl.tasks ?? [])
            : (cl.tasks ?? []).filter(t => !t.done);
        return { ...cl, tasks };
    });
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

// --- OwnTasksManagement filter actions ---
function toggleFlag(key) {
    user.value[key] = !user.value[key];

    if (key === 'checklist_has_projects' && user.value.checklist_has_projects) {
        user.value.checklist_no_projects = false;
    } else if (key === 'checklist_no_projects' && user.value.checklist_no_projects) {
        user.value.checklist_has_projects = false;
    }

    if (key === 'checklist_private_checklists' && user.value.checklist_private_checklists) {
        user.value.checklist_no_private_checklists = false;
    } else if (key === 'checklist_no_private_checklists' && user.value.checklist_no_private_checklists) {
        user.value.checklist_private_checklists = false;
    }

    saveFiltersNow();
}

function resetFilters() {
    user.value.checklist_has_projects = false;
    user.value.checklist_no_projects = false;
    user.value.checklist_private_checklists = false;
    user.value.checklist_no_private_checklists = false;
    user.value.checklist_completed_tasks = false;
    user.value.checklist_show_without_tasks = false;
    saveFiltersNow();
}

function chipClass(active) {
    return active
        ? 'border-indigo-300 bg-indigo-50/70 text-indigo-700'
        : 'border-gray-200 text-gray-600 hover:bg-gray-50';
}

function groupedChipClass(active) {
    return active
        ? 'bg-indigo-50/80 text-indigo-700 ring-1 ring-inset ring-indigo-200'
        : 'text-gray-600 hover:bg-gray-50';
}

function saveFiltersNow() {
    router.patch(
        route('user.update.checklist.filter', user.value.id),
        {
            checklist_has_projects: user.value.checklist_has_projects,
            checklist_no_projects: user.value.checklist_no_projects,
            checklist_private_checklists: user.value.checklist_private_checklists,
            checklist_no_private_checklists: user.value.checklist_no_private_checklists,
            checklist_completed_tasks: user.value.checklist_completed_tasks,
            checklist_show_without_tasks: user.value.checklist_show_without_tasks
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                filterSaved.value = true;
                setTimeout(() => (filterSaved.value = false), 3000);
            }
        }
    );
}

function updateChecklistStyleLocal(type) {
    checklistStyle.value = type;
    usePage().props.auth.user.checklist_style = type;
    router.patch(route('user.checklist.style', { user: user.value.id }), {
        checklist_style: type,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function sortTo(type) {
    currentSort.value = type;
    sortOpen.value = false;
    if (props.isInOwnTaskManagement && (type === 1 || type === 2)) {
        router.reload({
            data: { filter: type },
            only: ['public_checklists', 'private_checklists'],
            preserveScroll: true,
            preserveState: true
        });
    }
}

// Echo listener for real-time checklist updates
let echoChannel = null;

onMounted(() => {
    syncOpenedChecklists();

    if (props.project?.id) {
        echoChannel = Echo.private('project.' + props.project.id)
            .listen('.checklist.updated', () => {
                fetchChecklists();
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
