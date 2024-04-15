<template>
    <app-layout>
        <div class="max-w-screen-xl ml-14 mr-10">
            <div class="flex-wrap">
                <div class="flex flex-wrap">
                    <h2 class="headline1 w-full">{{$t('My tasks')}}</h2>
                    <Listbox as="div" class="sm:col-span-3 mb-8" @click="changeTasksToDisplay" v-model="selectedFilter">
                        <div class="relative">
                            <ListboxButton class="w-56 flex justify-between sDark py-2">
                                <div> {{ selectedFilter.name }}</div>
                                <div>
                                    <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </div>
                            </ListboxButton>

                            <ListboxOptions class="absolute w-56 bg-primary shadow-lg max-h-32 rounded-md focus:outline-none">
                                <ListboxOption as="template" class="p-2 text-sm"
                                    v-for="filter in filters"
                                    :key="filter.name"
                                    :value="filter"
                                    v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                        <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                            {{ filter.name }}
                                        </div>
                                        <div v-if="selected">
                                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </div>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </div>
                    </Listbox>

                    <!--     Task Index   -->
                    <div class="w-full">
                        <div v-for="task in tasksToDisplay" :key="task.id"  :id="task.id">
                            wefwef
                            <SingleTask :task="task" :first_project_tasks_tab_id="this.first_project_tasks_tab_id" />
                        </div>
                        <div v-for="task in money_source_task" :key="task.id" :id="task.id">
                            <SingleMoneySourceTask :task="task" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>

import Permissions from "@/mixins/Permissions.vue";


import AppLayout from '@/Layouts/AppLayout.vue'
import {CheckIcon, ChevronDownIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import SingleMoneySourceTask from "@/Pages/Tasks/Components/SingleMoneySourceTask.vue";
import SingleTask from "@/Pages/Tasks/Components/SingleTask.vue";

export default {
    mixins: [Permissions],
    name: "OwnTasksManagement",
    props: [
        'tasks',
        'money_source_task',
        'first_project_tasks_tab_id'
    ],
    components: {
        SingleTask,
        SingleMoneySourceTask,
        AppLayout,
        ChevronRightIcon,
        TeamIconCollection,
        Link,
        CheckIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon
    },
    mounted() {
        if(this.$page.props.urlParameters.taskId){
           const task = document.getElementById(this.$page.props.urlParameters.taskId);
           task.scrollIntoView();
        }
    },
    methods: {
        changeTasksToDisplay() {
            switch (this.selectedFilter.name) {
                case this.$t('By deadline'):
                    this.tasksToDisplay = this.tasks.data.filter(task => !task.done);
                    this.tasksToDisplay = this.tasksToDisplay.sort(function (a, b) {
                        return a.deadline > b.deadline
                    });
                    break;
                case this.$t('Completed tasks'):
                    this.tasksToDisplay = this.tasks.data.filter(task => task.done);
                    break;
                default:
                    this.tasksToDisplay = this.tasks.data.filter(task => !task.done);
                    this.tasksToDisplay = this.tasksToDisplay.sort(function (a, b) {
                        return a.checklistId > b.checklistId
                    });
            }
        },
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        },
        updateMoneySourceTaskStatus(task){
            this.$inertia.patch(route('money_source.tasks.update', {moneySourceTask: task.id}))
        }
    },
    data() {
        return {
            selectedFilter: {name: this.$t('According to checklists')},
            tasksToDisplay: this.tasks.data.filter(task => !task.done),
            doneTaskForm: useForm({
                done: false
            }),
            highlight: null,
            filters : [
                {name: this.$t('According to checklists')},
                {name: this.$t('By deadline')},
                {name: this.$t('Completed tasks')}
            ]
        }
    },
}
</script>

<style scoped>

</style>
