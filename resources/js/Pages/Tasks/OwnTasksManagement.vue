<template>
    <app-layout :title="$t('My tasks')">
        <div class="artwork-container">
            <div>
                <ChecklistFunctionBar
                    title="My ToDo-Lists"
                    :filters="filters"
                    :checklist_templates="checklist_templates"
                    :tab_id="first_project_tasks_tab_id"
                    is-in-own-task-management
                >
                    <template #search>
                        <div class="" v-if="!showSearch" @click="openSearchbar">
                            <IconSearch class="h-6 w-6 cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" />
                        </div>
                        <div v-if="showSearch">
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
                        </div>
                    </template>
                    <template #filter>
                        <BaseFilter only-icon="true" :left="false">
                            <div class="w-full">
                                <div class="flex justify-end mb-3">
                                    <span class="xxsLight cursor-pointer text-right w-full" @click="removeFilter">
                                        {{ $t('Reset') }}
                                    </span>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_has_projects"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('To-do lists with project') }}
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_no_projects"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('To-do lists without project') }}
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_private_checklists"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('Only personal to-do lists') }}
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_no_private_checklists"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('Only shared to-do lists') }}
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_completed_tasks"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('Show completed tasks') }}
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input @change="updateUserFilter" v-model="usePage().props.auth.user.checklist_show_without_tasks"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        {{ $t('Show to-do lists without tasks') }}
                                    </p>
                                </div>
                                <transition enter-active-class="duration-300 ease-out" enter-from-class="transform opacity-0" enter-to-class="opacity-100" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="transform opacity-0">
                                    <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg" v-show="filterWasSaved">
                                        {{ $t('The filter settings have been saved.') }}
                                    </div>
                                </transition>
                                <transition enter-active-class="duration-300 ease-out" enter-from-class="transform opacity-0" enter-to-class="opacity-100" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="transform opacity-0">
                                    <div class="my-3 text-xs bg-orange-600 px-3 py-1.5 text-white rounded-lg" v-show="willSaved">
                                        {{$t('The filter settings will be saved in 3 seconds.') }}
                                    </div>
                                </transition>
                            </div>
                        </BaseFilter>
                    </template>
                    <template #sort>
                        <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(1)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                    {{ $t('Project period ascending') }}
                                    <IconCheck class="w-5 h-5" v-if="currentSort === 1" />
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(2)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                    {{ $t('Project period descending') }}
                                    <IconCheck class="w-5 h-5" v-if="currentSort === 2" />
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="currentSort = 3"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                    {{ $t('ToDo-List name descending') }}
                                    <IconCheck class="w-5 h-5" v-if="currentSort === 3" />
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="currentSort = 4"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                    {{ $t('ToDo-List name ascending') }}
                                    <IconCheck class="w-5 h-5" v-if="currentSort === 4" />
                                </div>
                            </MenuItem>
                            <!--<MenuItem v-slot="{ active }">
                                <div @click="sortTo(5)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    ToDo Deadline aufsteigend
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(6)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    ToDo Deadline absteigend
                                </div>
                            </MenuItem>-->
                        </BaseMenu>
                    </template>
                </ChecklistFunctionBar>
            </div>

           <div class="mt-10">
               <div v-if="$page.props.auth.user.checklist_style === 'list'">
                   <ChecklistListView
                       :checklists="checklistsComputed"
                       is-in-own-task-management
                   />
               </div>

               <div v-else class="">
                   <ChecklistKanbanView
                       :checklists="checklistsComputed"
                       is-in-own-task-management
                   />
               </div>
           </div>

            <div class="my-20">
                <div class="headline2 mb-5">
                    {{ $t('Sources of funding')}} {{ $t('Tasks')}}
                </div>
                <div>
                    <div class="divide-y-2 divide-dashed" v-if="moneySourceTasks.length > 0">
                        <div v-for="task in moneySourceTasks" :key="task.id" :id="task.id" class="pt-3">
                            <SingleMoneySourceTask :task="task" />
                        </div>
                    </div>
                    <div v-else>
                        <AlertComponent
                            type="info"
                            :text="$t('There are no tasks related to the sources of funding.')"
                            show-icon
                            icon-size="h-6 w-6"
                            text-size="text-base"
                        />
                    </div>
                </div>
            </div>
        </div>


    </app-layout>
</template>

<script setup>
import {computed, nextTick, ref, watch} from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SingleMoneySourceTask from "@/Pages/Tasks/Components/SingleMoneySourceTask.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {IconSearch, IconX} from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import {router, usePage} from "@inertiajs/vue3";
import { IconCheck } from "@tabler/icons-vue";
import debounce from "lodash.debounce";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const $t = useTranslation();

const props = defineProps({
    tasks: Array,
    private_checklists: Array,
    money_source_task: Array,
    first_project_tasks_tab_id: Number,
    checklists: Object,
    checklist_templates: Array,
});

const search = ref('');
const showSearch = ref(false);
const filterWasSaved = ref(false);
const willSaveInSec = ref(3000);
const willSaved = ref(false);

const currentSort = ref(usePage().props.urlParameters?.filter > 0 ? parseInt(usePage().props.urlParameters?.filter) : 0);

const filters = ref([
    { name: $t('According to checklists'), type: 1 },
    { name: $t('By deadline'), type: 2 },
    { name: $t('Completed tasks'), type: 3 }
]);


const filteredChecklists = computed(() => {
    return props?.checklists?.filter(checklist => {
        let include = true;
        if (search.value) {
            include = checklist.name.toLowerCase().includes(search.value.toLowerCase()) || checklist.tasks.some(task => task.name.toLowerCase().includes(search.value.toLowerCase()));
        }
        if (usePage().props.auth.user.checklist_has_projects) include = include && checklist.hasProject === true;
        if (usePage().props.auth.user.checklist_no_projects) include = include && checklist.hasProject === false;
        if (usePage().props.auth.user.checklist_private_checklists) include = include && checklist.private === true;
        if (usePage().props.auth.user.checklist_no_private_checklists) include = include && checklist.private === false;

        // entferne checklisten die nur erledigt aufgaben haben oder keine aufgaben
        if (usePage().props.auth.user.checklist_show_without_tasks) {
            include = include && checklist.tasks.length >= 0;
        } else {
            include = include && checklist.tasks.length > 0 && checklist.tasks.some(task => !task.done);
        }

        return include;
    });

    // sort checklists by name if currentSort is 3, or 4

});

const checklistsComputed = computed(() => {
    const checklists = filteredChecklists.value.map(checklist => {
        let tasks = checklist.tasks;
        if (!usePage().props.auth.user.checklist_completed_tasks) {
            tasks = tasks.filter(task => !task.done);
        }

        return {
            ...checklist,
            tasks
        };
    });

    if (currentSort.value === 3) {
        return checklists.sort((a, b) => a.name.localeCompare(b.name));
    } else if (currentSort.value === 4) {
        return checklists.sort((a, b) => b.name.localeCompare(a.name));
    } else {
        return checklists;
    }
});



const moneySourceTasks = ref(props.money_source_task)

const sortTo = (type) => {
    currentSort.value = type;
    router.reload({
        data: {
            filter: type
        },
        only: ['checklists']
    })
}
const removeSearch = () => {
    search.value = '';
    showSearch.value = false;
}

const removeFilter = () => {
    usePage().props.auth.user.checklist_has_projects = false;
    usePage().props.auth.user.checklist_no_projects = false;
    usePage().props.auth.user.checklist_private_checklists = false;
    usePage().props.auth.user.checklist_no_private_checklists = false;
    usePage().props.auth.user.checklist_completed_tasks = false;
    usePage().props.auth.user.checklist_show_without_tasks = true;

    updateUserFilter();
}

const openSearchbar = () => {
    showSearch.value = true;
    nextTick(() => {
        document.getElementById('userSearch').focus();
    });
}

const updateUserFilter = () => {
    willSaved.value = true;
    debounce(() => {
        router.patch(route('user.update.checklist.filter', usePage().props.auth.user.id), {
            checklist_has_projects: usePage().props.auth.user.checklist_has_projects,
            checklist_no_projects: usePage().props.auth.user.checklist_no_projects,
            checklist_private_checklists: usePage().props.auth.user.checklist_private_checklists,
            checklist_no_private_checklists: usePage().props.auth.user.checklist_no_private_checklists,
            checklist_completed_tasks: usePage().props.auth.user.checklist_completed_tasks,
            checklist_show_without_tasks: usePage().props.auth.user.checklist_show_without_tasks,
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                willSaved.value = false;
                filterWasSaved.value = true;
                setTimeout(() => {
                    filterWasSaved.value = false;
                }, 3000);
            }
        });
    }, willSaveInSec.value)();
}


</script>

<style scoped>
</style>
