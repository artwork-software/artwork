<template>
    <app-layout :title="$t('My tasks')">
        <div class="ml-14">
            <div>
                <ChecklistFunctionBar
                    title="My tasks"
                    :filters="filters"
                    is-in-own-task-management
                >
                    <template #search>
                        <div class="" v-if="!showSearch" @click="showSearch = true">
                            <IconSearch class="h-6 w-6" />
                        </div>
                        <div v-if="showSearch">
                            <div class="relative -mt-5">
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
                    <template #filter>
                        <BaseFilter only-icon="true" :left="false">
                            <div class="w-full">
                                <div class="flex justify-end mb-3">
                                    <span class="xxsLight cursor-pointer text-right w-full" @click="removeFilter">
                                        {{ $t('Reset') }}
                                    </span>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input v-model="hasProjects"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        ToDo-Listen mit Projekt
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input v-model="noProjects"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        ToDo-Listen Ohne Projekt
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input v-model="privateChecklists"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        Private ToDo-Listen
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input v-model="noPrivateChecklists"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        keine persönlichen ToDo-Listen
                                    </p>
                                </div>
                                <div class="flex max-h-8 mb-3 mt-3">
                                    <input v-model="showDoneTasks"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <p class=" ml-4 my-auto text-sm text-secondary">
                                        Abgeschlossene Aufgaben anzeigen
                                    </p>
                                </div>
                            </div>
                        </BaseFilter>
                    </template>
                    <template #sort>
                        <BaseMenu show-sort-icon dots-size="h-7 w-7">
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(1)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    Projektzeitraum aufsteigend
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(2)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    Projektzeitraum absteigend
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(3)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    Checkliste Name aufsteigend
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(4)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    Checkliste Name absteigend
                                </div>
                            </MenuItem>
                            <!--<MenuItem v-slot="{ active }">
                                <div @click="sortTo(5)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    ToDo Deadline aufsteigend
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="sortTo(6)"
                                     :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    ToDo Deadline absteigend
                                </div>
                            </MenuItem>-->
                        </BaseMenu>
                    </template>
                </ChecklistFunctionBar>
            </div>

            <div v-if="$page.props.user.checklist_style === 'list'">
                <ChecklistListView
                    :checklists="checklistsComputed"
                    is-in-own-task-management
                />
            </div>

            <div v-else class="-mx-10 bg-artwork-project-background px-10 py-10">
                <ChecklistKanbanView
                    :checklists="checklistsComputed"
                    is-in-own-task-management
                />
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
import {computed, ref} from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SingleMoneySourceTask from "@/Pages/Tasks/Components/SingleMoneySourceTask.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {IconFilter, IconSearch, IconX} from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";

const $t = useTranslation();

const props = defineProps({
    tasks: Array,
    private_checklists: Array,
    money_source_task: Array,
    first_project_tasks_tab_id: Number,
    checklists: Object
});

const search = ref('');
const showSearch = ref(false);

// filter options
const hasProjects = ref(false);
const noProjects = ref(false);
const privateChecklists = ref(false);
const noPrivateChecklists = ref(false);
const showDoneTasks = ref(false);

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
        if (hasProjects.value) include = include && checklist.hasProject === true;
        if (noProjects.value) include = include && checklist.hasProject === false;
        if (privateChecklists.value) include = include && checklist.private === true;
        if (noPrivateChecklists.value) include = include && checklist.private === false;
        return include;
    });
});

const checklistsComputed = computed(() => {
    return filteredChecklists.value.map(checklist => {
        let tasks = checklist.tasks;
        if (!showDoneTasks.value) {
            tasks = tasks.filter(task => !task.done);
        }
        return {
            ...checklist,
            tasks
        };
    });
});


const moneySourceTasks = ref(props.money_source_task)

const sortTo = (type) => {
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
    hasProjects.value = false;
    noProjects.value = false;
    privateChecklists.value = false;
    noPrivateChecklists.value = false;
    showDoneTasks.value = false;
}

</script>

<style scoped>
</style>
