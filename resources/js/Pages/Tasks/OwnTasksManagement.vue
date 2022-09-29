<template>
    <app-layout>
        <div class="max-w-screen-xl my-12 ml-14 mr-10">
            <div class="flex-wrap">
                <div class="flex flex-wrap">
                    <h2 class="font-bold font-lexend text-3xl w-full">Meine Aufgaben</h2>
                    <Listbox as="div" class="sm:col-span-3 mb-8" @click="changeTasksToDisplay" v-model="selectedFilter">
                        <div class="relative">
                            <ListboxButton class="w-56 flex justify-between font-semibold py-2">
                                <div> {{ selectedFilter.name }}</div>
                                <div>
                                    <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </div>
                            </ListboxButton>

                            <ListboxOptions class="bg-primary shadow-lg max-h-32 rounded-md focus:outline-none">
                                <ListboxOption as="template" class="p-2 text-sm"
                                    v-for="filter in filters"
                                    :key="filter.name"
                                    :value="filter"
                                    v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                        <div :class="[selected ? 'font-bold text-white' : '', 'truncate']">
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
                        <div v-for="task in tasksToDisplay" :key="task.id">

                            <div class="flex w-full flex-wrap md:flex-nowrap align-baseline">
                                <div class="flex w-full flex-grow">
                                    <input @change="updateTaskStatus(task)"
                                        v-model="task.done"
                                        type="checkbox"
                                        class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-gray-300"/>
                                    <div class="ml-4 my-auto text-lg font-bold"
                                        :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                                        {{ task.name }}
                                    </div>
                                    <div v-if="!task.done && task.deadline"
                                        class="ml-2 my-auto text-sm "
                                        :class="task.isDeadlineInFuture ? 'text-error ' : ''">
                                        bis {{ task.humanDeadline }}
                                    </div>
                                </div>

                                <div class="my-auto flex mr-3"
                                    v-for="department in task.departments">
                                    <TeamIconCollection
                                        :iconName="department.svg_name"
                                        :alt="department.name"
                                        class="ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                </div>
                                <div v-show="! task.isPrivate"
                                    class="my-auto">
                                    <img class="h-9 w-9 rounded-full"
                                        :src="$page.props.user.profile_photo_url"
                                        alt=""/>
                                </div>
                            </div>

                            <Link :href="route('projects.show',{project: task.projectId})"
                                class="text-sm my-1 flex ml-10">
                                {{ task.projectName }}
                                <ChevronRightIcon class="h-5 w-5 my-auto mx-3 text-secondary " aria-hidden="true"/>
                                {{ task.checklistName }}
                            </Link>

                            <div class="ml-10 my-3 text-secondary">
                                {{ task.description }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>

const filters = [
    {name: 'Nach Checklisten'},
    {name: 'Nach Deadline'},
    {name: 'Erledigte Aufgaben'}
]

import AppLayout from '@/Layouts/AppLayout.vue'
import {CheckIcon, ChevronDownIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";

export default {
    name: "OwnTasksManagement",
    props: ['tasks'],
    computed: {},
    components: {
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
    methods: {
        changeTasksToDisplay() {
            switch (this.selectedFilter.name) {
                case 'Nach Deadline':
                    this.tasksToDisplay = this.tasks.data.filter(task => !task.done);
                    console.log(this.tasksToDisplay)
                    this.tasksToDisplay = this.tasksToDisplay.sort(function (a, b) {
                        return a.deadline > b.deadline
                    });
                    break;
                case 'Erledigte Aufgaben':
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
        }
    },
    data() {
        return {
            selectedFilter: filters[0],
            tasksToDisplay: this.tasks.data.filter(task => !task.done),
            doneTaskForm: useForm({
                done: false
            }),
        }
    },
    setup() {
        return {
            filters
        }
    }
}
</script>

<style scoped>

</style>
