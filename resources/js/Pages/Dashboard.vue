<template>
    <app-layout>
        <div class="py-4 grid grid-cols-6">
            <!-- Greetings Div -->
            <div class="col-span-4 mr-12 overflow-x-auto">
                <div class="ml-20 mt-10">
                    <h2 class="text-3xl font-lexend font-black text-primary flex mb-4">Hallo {{
                            $page.props.user.first_name
                        }}</h2>
                    <p class="text-secondary tracking-tight leading-6 sub">Herzlich willkommen im artwork tool! Um dich
                        hier
                        gut zurechtzufinden, haben wir die Hilfetexte aktiviert.<br/>
                        Du kannst sie oben neben deinem Nutzernamen ausstellen.<br/>
                    </p>
                    <p class="mt-2 text-secondary tracking-tight leading-6 sub">Viel Spaß beim Loslegen!</p>
                </div>

                <!-- Calendar Div -->
                <div class="relative">
                    <Link :href="route('events.daily_management', {wanted_day:new Date()})"
                          class="flex justify-end uppercase text-sm text-secondary right-0 items-end subpixel-antialiased absolute mt-14">
                        Alle Ansehen
                    </Link>
                    <DailyCalendar calendar-type="dashboard" :hours_of_day="hours_of_day" :rooms="rooms"
                                   :projects="projects" :event_types="event_types" :areas="areas"
                                   :shown_day_formatted="shown_day_formatted" :shown_day_local="shown_day_local"
                                   :events_without_room="events_without_room"/>
                </div>
            </div>
            <!-- Task Div -->
            <div class=" col-span-2 px-4 mt-20 overflow-y-auto">
                <div class="flex w-full">
                    <h2 class="font-bold font-lexend text-2xl w-full">Meine Aufgaben</h2>
                    <Link :href="route('tasks.own')"
                          class="flex justify-end uppercase text-sm text-secondary w-full items-end subpixel-antialiased">
                        Alle Ansehen
                    </Link>
                </div>
                <div class="mt-10" v-for="task in this.sortedTasksDeadline">
                    <div :key="task.id">
                        <div>
                            <div class="flex w-full">
                                <input @change="updateTaskStatus(task)" v-model="task.done"
                                       type="checkbox"
                                       class="ring-offset-0 my-auto cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p class="ml-4 my-auto text-lg font-black text-sm w-full"
                                   :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                                    {{ task.name }}</p>
                                <div v-show="task.departments.length > 0" class="my-auto shrink-0 -mr-3"
                                     v-for="department in task.departments">
                                    <TeamIconCollection :data-tooltip-target="department.name" :iconName="department.svg_name" :alt="department.name"
                                                        class="shrink-0 ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                    <TeamTooltip :team="department"/>
                                </div>
                                <div v-show="task.checklist.user_id !== null" class="my-auto">
                                    <img class="h-9 w-9 rounded-full"
                                         :src="$page.props.user.profile_photo_url"
                                         alt=""/>
                                </div>
                            </div>
                            <div class="flex w-full ml-8">
                                    <span v-if="!task.done && task.deadline"
                                          class="ml-2 my-auto text-sm subpixel-antialiased"
                                          :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{
                                            task.deadline
                                        }}</span>
                            </div>
                        </div>
                        <div class="flex text-sm mt-0.5 w-full items-center ml-10">
                            <Link
                                :href="route('projects.show',{project: task.project.id, month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
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
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {ref, onMounted} from 'vue'
import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsHorizontalIcon,
    CalendarIcon,
} from '@heroicons/vue/solid'
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from '@headlessui/vue'
import DailyCalendar from "@/Layouts/Components/DailyCalendar";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import {Inertia} from "@inertiajs/inertia";


const container = ref(null)
const containerNav = ref(null)
const containerOffset = ref(null)

onMounted(() => {
    // Set the container scroll position based on the current time.
    const currentMinute = new Date().getHours() * 60
    container.value.scrollTop =
        ((container.value.scrollHeight - containerNav.value.offsetHeight - containerOffset.value.offsetHeight) *
            currentMinute) /
        1440
})

export default defineComponent({
    props: ['rooms', 'tasks', 'event_types', 'days_this_month', 'areas', 'projects', 'month_events', 'events_without_room', 'hours_of_day', 'shown_day_formatted', 'shown_day_local'],
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
        DailyCalendar,
        TeamIconCollection,
        Link,
        TeamTooltip
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
        }
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
        }
    },

})
</script>
