<template>
    <app-layout title="Meine Aufgaben">
        <div class="max-w-screen-xl my-12 ml-20 mr-10">
            <div class="flex-wrap">
                <div class="flex flex-wrap">
                    <h2 class="font-bold font-lexend text-3xl w-full">Meine Aufgaben</h2>
                    <Listbox as="div" class="sm:col-span-3 mb-8" @click="changeTasksToDisplay" v-model="selectedFilter">
                        <div class="relative">
                            <ListboxButton
                                class="w-56 bg-white relative w-full font-semibold pr-20 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center">
                                            <span>{{ selectedFilter.name }}</span>
                                        </span>
                                <span
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="filter in filters"
                                                   :key="filter.name"
                                                   :value="filter"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ filter.name }}
                                                    </span>
                                            <span
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                    <div class="flex flex-wrap w-full">
                        <div class="flex w-full" v-for="task in tasksToDisplay">
                            <div class="flex w-full flex-wrap" :key="task.id">
                                <div class="flex w-full grid grid-cols-6">

                                    <div class="flex w-full col-span-5 w-full">
                                        <input @change="updateTaskStatus(task)" v-model="task.done"
                                               type="checkbox"
                                               class="ring-offset-0 my-auto cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                        <p class="ml-4 my-auto text-lg font-black text-sm"
                                           :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                                            {{ task.name }}</p>
                                        <span v-if="!task.done && task.deadline"
                                              class="ml-2 my-auto text-sm subpixel-antialiased"
                                              :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'text-error' : ''">bis {{
                                                task.deadline
                                            }}</span>

                                    </div>
                                    <div class="col-span-1 flex">
                                        <div v-show="task.departments.length > 0" class="my-auto flex -mr-3"
                                             v-for="department in task.departments">
                                            <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                                class="ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                        </div>
                                        <div v-show="task.checklist.user_id !== null" class="my-auto">
                                            <img class="h-9 w-9 rounded-full"
                                                 :src="$page.props.user.profile_photo_url"
                                                 alt=""/>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex text-sm mt-0.5 w-full items-center ml-10">
                                    <Link :href="route('projects.show',{project: task.project.id, month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
                                          class="cursor-pointer text-secondary flex subpixel-antialiased">
                                        {{ task.project.name }}
                                        <ChevronRightIcon class="h-5 w-5 my-auto text-secondary subpixel-antialiased"
                                                          aria-hidden="true"/>
                                        <span class="text-sm ml-4 subpixel-antialiased text-secondary flex">
                                        {{ task.checklist.name }}
                                        </span>
                                    </Link>
                                </div>

                                <div class="ml-10 my-3 text-secondary">
                                    {{ task.description }}
                                </div>
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
    computed: {
        sortedTasksDeadline: function () {
            let taskCopy = this.tasks.slice();
            let undoneSortedTasksDeadline = taskCopy.filter(task => task.done === false);

            function compare(a, b) {
                if (b.deadline === null) {
                    return -1;
                }
                if (a.deadline === null) {
                    return 1;
                }
                if (a.deadline < b.deadline)
                    return -1;
                if (a.deadline > b.deadline)
                    return 1;
                return 0;
            }

            return undoneSortedTasksDeadline.sort(compare);
        },
        doneTasks: function () {
            let filteredTasks = this.tasks.filter(task => task.done === true);
            return filteredTasks;
        },
    },
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
            if (this.selectedFilter.name === 'Nach Deadline') {
                this.tasksToDisplay = this.sortedTasksDeadline;
            } else if (this.selectedFilter.name === 'Nach Checklisten') {
                this.tasksToDisplay = this.tasks.filter(task => task.done === false);
            } else if (this.selectedFilter.name === 'Erledigte Aufgaben') {
                this.tasksToDisplay = this.doneTasks;
            }
        },
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        }
    },
    data() {
        return {
            selectedFilter: {name: 'Nach Checklisten'},
            tasksToDisplay: this.tasks.filter(task => task.done === false),
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
