<template>
    <app-layout>

        <div class="py-4 flex">
            <!-- Greetings Div -->
            <div class="mr-2 w-4/6">
                <div class="ml-12 mt-10">
                    <h2 class="headline1 flex mb-4">Hallo {{ $page.props.user.first_name }}</h2>
                    <p class="xsLight">
                        Herzlich willkommen im artwork! Um dich hier gut zurechtzufinden, haben wir die Hilfetexte
                        aktiviert.<br/>
                        Du kannst sie oben neben deinem Nutzernamen ausstellen.<br/>
                    </p>
                    <p class="mt-2 xsLight">Viel Spaß beim Loslegen!</p>
                </div>
                <div>
                    <button class="bg-buttonBlue text-secondaryHover rounded-full p-2 font-semibold" @click="this.showIndividualCalendar = !showIndividualCalendar">
                        Kalender-Switch
                    </button>
                    <div v-if="showIndividualCalendar">
                    <IndividualCalendarComponent :calendarData="calendar" :rooms="rooms" :event-types="eventTypes" :days="days" />
                    </div>
                    <div v-else>
                        <div class="min-w-[50%] mt-5 overflow-x-auto px-2">
                                            <CalendarComponent :eventTypes=this.eventTypes initial-view="day"/>
                        </div>
                    </div>
                </div>


                <!-- Calendar Div -->

            </div>
            <!-- Task Div -->
            <div class="px-6 mt-20 overflow-y-auto">
                <div class="flex">
                    <h2 class="headline2">Meine Aufgaben</h2>
                    <Link :href="route('tasks.own')"
                        class="flex ml-4 justify-end uppercase xsLight items-end ">
                        Alle Ansehen
                    </Link>
                </div>
                <div class="mt-10" v-for="task in this.sortedTasksDeadline">
                    <div :key="task.id">
                        <div>
                            <div class="flex">
                                <input @change="updateTaskStatus(task)" v-model="task.done"
                                    type="checkbox"
                                    class="ring-offset-0 my-auto cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p class="ml-4 my-auto mDark w-full"
                                    :class="task.done ? 'text-secondary line-through' : ''">
                                    {{ task.name }}</p>
                                <div v-show="task.departments.length > 0" class="my-auto shrink-0 -mr-3"
                                    v-for="department in task.departments">
                                    <TeamIconCollection :data-tooltip-target="department.name" :iconName="department.svg_name" :alt="department.name"
                                        class="shrink-0 ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                    <TeamTooltip :team="department"/>
                                </div>
                                <div v-show="task.checklist.user_id !== null" class="my-auto">
                                    <img class="h-9 w-9 rounded-full object-cover"
                                        :src="$page.props.user.profile_photo_url"
                                        alt=""/>
                                </div>
                            </div>
                            <div class="flex w-full ml-8">
                                    <span v-if="!task.done && task.deadline"
                                        class="ml-2 my-auto xsLight subpixel-antialiased"
                                        :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{
                                            task.deadline
                                                                                                                                                   }}</span>
                            </div>
                        </div>
                        <div class="flex xsLight mt-0.5 w-full items-center ml-10">
                            <Link
                                :href="route('projects.show',{project: task.project.id, openTab:'calendar'})"
                                class="cursor-pointer text-secondary flex subpixel-antialiased">
                                {{ task.project.name }}
                                <ChevronRightIcon class="h-5 w-5 my-auto text-secondary subpixel-antialiased"
                                    aria-hidden="true"/>
                                <span class="text-sm ml-4 subpixel-antialiased text-secondary flex">
                                        {{ task.checklist.name }}
                                        </span>
                            </Link>
                        </div>

                        <div class="ml-10 my-3 xsLight">
                            {{ task.description }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsHorizontalIcon,
} from '@heroicons/vue/solid'
import {Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import {Inertia} from "@inertiajs/inertia";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";

export default defineComponent({
    props: ['tasks', 'projects', 'eventTypes', 'calendar', 'rooms','days'],
    components: {
        AppLayout,
        CalendarIcon,
        ChevronRightIcon,
        Menu,
        MenuItem,
        MenuButton,
        MenuItems,
        ChevronLeftIcon,
        DotsHorizontalIcon,
        ChevronDownIcon,
        CalendarComponent,
        TeamIconCollection,
        Link,
        TeamTooltip,
        IndividualCalendarComponent
    },
    created() {
        Echo.private('events')
            .listen('OccupancyUpdated', () => {
                Inertia.reload({only: ['rooms']})
            });
    },
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
    },
    methods: {
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        },
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
            showIndividualCalendar: true,
        }
    },

})
</script>
